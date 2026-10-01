<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\IndexReadingPlanRequest;
use App\Http\Requests\StoreReadingPlanRequest;
use App\Http\Requests\UpdateReadingPlanRequest;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReadingPlanController extends Controller
{
    public function index(IndexReadingPlanRequest $request): View
    {
        $filters = $request->validated();

        $readingPlans = $request->user()
            ->readingPlans()
            ->with('book')
            ->when(
                $filters['status'] ?? null,
                fn (Builder $query, string $status): Builder => $query->where('status', $status)
            )
            ->orderBy('target_date')
            ->orderBy('id')
            ->paginate(10)
            ->appends($filters);

        return view('reading-plans.index', compact('readingPlans'));
    }

    public function create(): View
    {
        $this->authorize('create', ReadingPlan::class);

        $books = Book::query()
            ->orderBy('title')
            ->orderBy('id')
            ->get();

        return view('reading-plans.create', compact('books'));
    }

    public function store(StoreReadingPlanRequest $request): RedirectResponse
    {
        $this->authorize('create', ReadingPlan::class);
        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated): void {
            User::query()
                ->whereKey($request->user()->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $alreadyExists = ReadingPlan::query()
                ->whereBelongsTo($request->user())
                ->where('book_id', $validated['book_id'])
                ->where('status', ReadingPlanStatus::InProgress)
                ->exists();

            if ($alreadyExists) {
                throw ValidationException::withMessages([
                    'book_id' => 'この書籍は既に進行中の読書計画が存在します。',
                ]);
            }

            $request->user()->readingPlans()->create([
                'book_id' => $validated['book_id'],
                'target_date' => $validated['target_date'],
                'status' => ReadingPlanStatus::InProgress,
                'completed_at' => null,
            ]);
        });

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を作成しました。');
    }

    public function edit(ReadingPlan $readingPlan): View|RedirectResponse
    {
        $this->authorize('update', $readingPlan);

        if ($readingPlan->status === ReadingPlanStatus::Completed) {
            return redirect()
                ->route('reading-plans.index')
                ->with('error', '完了済みの読書計画は編集できません。');
        }

        $readingPlan->load('book');

        return view('reading-plans.edit', compact('readingPlan'));
    }

    public function update(
        UpdateReadingPlanRequest $request,
        ReadingPlan $readingPlan
    ): RedirectResponse {
        if ($readingPlan->status === ReadingPlanStatus::Completed) {
            return redirect()
                ->route('reading-plans.index')
                ->with('error', '完了済みの読書計画は編集できません。');
        }

        $readingPlan->update([
            'target_date' => $request->validated('target_date'),
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を更新しました。');
    }

    public function complete(ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('update', $readingPlan);

        if ($readingPlan->status === ReadingPlanStatus::Completed) {
            return redirect()
                ->route('reading-plans.index')
                ->with('error', 'この読書計画は既に完了しています。');
        }

        $readingPlan->update([
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を完了しました。');
    }

    public function destroy(ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('delete', $readingPlan);

        DB::transaction(function () use ($readingPlan): void {
            $readingPlan->user
                ->notifications()
                ->where('data->reading_plan_id', $readingPlan->id)
                ->delete();

            $readingPlan->delete();
        });

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を削除しました。');
    }
}
