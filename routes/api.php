<?php

use App\Http\Controllers\Api\V1\BookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/books', [BookController::class, 'index'])
        ->name('api.v1.books.index');
    Route::post('/books', [BookController::class, 'store'])
        ->name('api.v1.books.store');
    Route::get('/books/{book}', [BookController::class, 'show'])
        ->name('api.v1.books.show');
    Route::put('/books/{book}', [BookController::class, 'update'])
        ->name('api.v1.books.update');
    Route::delete('/books/{book}', [BookController::class, 'destroy'])
        ->name('api.v1.books.destroy');
});
