<?php

namespace App\Models;

use App\Enums\ReadingPlanStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'book_id',
        'target_date',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'target_date' => 'date',
        'status' => ReadingPlanStatus::class,
        'completed_at' => 'datetime',
    ];

    /** @return BelongsTo 読書計画を作成したユーザーとの関連 */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo 読書計画の対象書籍との関連 */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
