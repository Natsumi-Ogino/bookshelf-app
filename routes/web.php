<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\BookIsbnLookupController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\ReadingPlanController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ReviewLikeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BookController::class, 'index'])
    ->name('books.index');

Route::get('/ranking', [RankingController::class, 'index'])
    ->name('ranking.index');

Route::middleware('auth')->group(function () {
    Route::get('/reports', ReportController::class)
        ->name('reports.index');
    Route::post('/reading-plans/{reading_plan}/complete', [ReadingPlanController::class, 'complete'])
        ->name('reading-plans.complete');
    Route::resource('reading-plans', ReadingPlanController::class)
        ->except('show');
    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])
        ->name('notifications.read');
    Route::get('/books/isbn/{isbn}', BookIsbnLookupController::class)
        ->name('books.isbn.lookup');
    Route::resource('genres', GenreController::class);
    Route::get('/books/create', [BookController::class, 'create'])
        ->name('books.create');
    Route::post('/books', [BookController::class, 'store'])
        ->name('books.store');
    Route::get('/books/{book}/edit', [BookController::class, 'edit'])
        ->name('books.edit');
    Route::put('/books/{book}', [BookController::class, 'update'])
        ->name('books.update');
    Route::delete('/books/{book}', [BookController::class, 'destroy'])
        ->name('books.destroy');

    Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])
        ->name('reviews.store');
    Route::get('/reviews/{review}/edit', [ReviewController::class, 'edit'])
        ->name('reviews.edit');
    Route::put('/reviews/{review}', [ReviewController::class, 'update'])
        ->name('reviews.update');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])
        ->name('reviews.destroy');
    Route::post('/reviews/{review}/like', [ReviewLikeController::class, 'toggle'])
        ->name('reviews.like');
    Route::get('/favorites', [FavoriteController::class, 'index'])
        ->name('favorites.index');
    Route::post('/books/{book}/favorites', [FavoriteController::class, 'toggle'])
        ->name('favorites.toggle');
});

Route::get('/books/{book}', [BookController::class, 'show'])
    ->name('books.show');
