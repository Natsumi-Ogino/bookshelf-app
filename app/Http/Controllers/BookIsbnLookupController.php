<?php

namespace App\Http\Controllers;

use App\Http\Requests\LookupBookByIsbnRequest;
use App\Services\GoogleBooksService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class BookIsbnLookupController extends Controller
{
    public function __invoke(
        LookupBookByIsbnRequest $request,
        GoogleBooksService $googleBooks
    ): JsonResponse {
        try {
            $bookData = $googleBooks->findByIsbn(
                (string) $request->validated('isbn')
            );
        } catch (ConnectionException|RequestException $exception) {
            Log::warning('Google Books API request failed.', [
                'exception' => $exception::class,
            ]);

            return response()->json([
                'error' => 'API通信エラーが発生しました。',
            ], 500);
        }

        if ($bookData === null) {
            return response()->json([
                'error' => '書籍が見つかりませんでした。',
            ], 404);
        }

        return response()->json($bookData);
    }
}
