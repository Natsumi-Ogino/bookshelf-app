<?php

use App\Http\Controllers\Api\V1\BookController;
use App\Http\Controllers\Api\V1\TokenController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/tokens', [TokenController::class, 'store'])
        ->middleware('throttle:token-issuance')
        ->name('api.v1.tokens.store');

    Route::get('/books', [BookController::class, 'index'])
        ->name('api.v1.books.index');
    Route::get('/books/{book}', [BookController::class, 'show'])
        ->name('api.v1.books.show');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::delete('/tokens/current', [TokenController::class, 'destroy'])
            ->name('api.v1.tokens.destroy');
        Route::post('/books', [BookController::class, 'store'])
            ->name('api.v1.books.store');
        Route::put('/books/{book}', [BookController::class, 'update'])
            ->name('api.v1.books.update');
        Route::delete('/books/{book}', [BookController::class, 'destroy'])
            ->name('api.v1.books.destroy');
    });
});
