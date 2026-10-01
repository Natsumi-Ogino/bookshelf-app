<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Genre extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /** @return BelongsToMany ジャンルに紐づく書籍との関連 */
    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class);
    }
}
