<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">読書計画</h2>
            <a href="{{ route('reading-plans.create') }}" class="rounded bg-blue-500 px-4 py-2 font-bold text-white hover:bg-blue-700">
                新規計画作成
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            @error('status')
                <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-red-700">
                    {{ $message }}
                </div>
            @enderror

            <div class="mb-6 rounded-lg bg-white p-6 shadow-sm">
                <form action="{{ route('reading-plans.index') }}" method="GET">
                    <label for="status" class="block text-sm font-medium text-gray-700">状態で絞り込む</label>
                    <div class="mt-2 flex gap-3">
                        <select name="status" id="status" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">すべて</option>
                            @foreach (\App\Enums\ReadingPlanStatus::cases() as $status)
                                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                                    {{ $status->label() }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="shrink-0 whitespace-nowrap rounded bg-gray-700 px-5 py-2 font-bold text-white hover:bg-gray-800">絞り込む</button>
                    </div>
                </form>
            </div>

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @forelse ($readingPlans as $readingPlan)
                        <div class="border-b border-gray-200 py-5 last:border-b-0">
                            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <h3 class="text-lg font-bold">
                                        <a href="{{ route('books.show', $readingPlan->book) }}" class="text-blue-600 hover:underline">
                                            {{ $readingPlan->book->title }}
                                        </a>
                                    </h3>
                                    <p class="text-sm text-gray-600">{{ $readingPlan->book->author }}</p>
                                    <p class="mt-2">期日：{{ $readingPlan->target_date->format('Y/m/d') }}</p>
                                    <span class="mt-2 inline-block rounded-full px-3 py-1 text-sm font-semibold
                                        {{ $readingPlan->status === \App\Enums\ReadingPlanStatus::Completed ? 'bg-green-100 text-green-700' : '' }}
                                        {{ $readingPlan->status === \App\Enums\ReadingPlanStatus::Expired ? 'bg-red-100 text-red-700' : '' }}
                                        {{ $readingPlan->status === \App\Enums\ReadingPlanStatus::InProgress ? 'bg-blue-100 text-blue-700' : '' }}">
                                        {{ $readingPlan->status->label() }}
                                    </span>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    @if ($readingPlan->status !== \App\Enums\ReadingPlanStatus::Completed)
                                        <a href="{{ route('reading-plans.edit', $readingPlan) }}" class="rounded bg-yellow-500 px-4 py-2 font-bold text-white hover:bg-yellow-600">編集</a>
                                        <form action="{{ route('reading-plans.complete', $readingPlan) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="rounded bg-green-500 px-4 py-2 font-bold text-white hover:bg-green-600">読了</button>
                                        </form>
                                    @endif

                                    <form action="{{ route('reading-plans.destroy', $readingPlan) }}" method="POST" onsubmit="return confirm('この読書計画を削除しますか？');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded bg-red-500 px-4 py-2 font-bold text-white hover:bg-red-700">削除</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500">該当する読書計画はありません。</p>
                    @endforelse

                    @if ($readingPlans->hasPages())
                        <div class="mt-6">
                            {{ $readingPlans->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
