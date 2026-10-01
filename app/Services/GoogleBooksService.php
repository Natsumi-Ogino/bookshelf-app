<?php

namespace App\Services;

use Illuminate\Http\Client\Factory;

class GoogleBooksService
{
    public function __construct(
        private readonly Factory $http
    ) {}

    /**
     * ISBNを使ってGoogle Books APIから書籍情報を取得します。
     *
     * @param  string  $isbn  検索する13桁のISBN
     * @return array{
     *     title: ?string,
     *     author: ?string,
     *     published_date: ?string,
     *     description: ?string,
     *     image_url: ?string
     * }|null
     */
    public function findByIsbn(string $isbn): ?array
    {
        $query = [
            'q' => "isbn:{$isbn}",
        ];

        $apiKey = config('services.google_books.key');

        if (is_string($apiKey) && $apiKey !== '') {
            $query['key'] = $apiKey;
        }

        $response = $this->http
            ->acceptJson()
            ->connectTimeout(
                (int) config(
                    'services.google_books.connect_timeout'
                )
            )
            ->timeout(
                (int) config(
                    'services.google_books.timeout'
                )
            )
            ->get(
                (string) config('services.google_books.endpoint'),
                $query
            );

        $response->throw();

        $volumeInfo = $response->json('items.0.volumeInfo');

        if (! is_array($volumeInfo)) {
            return null;
        }

        return [
            'title' => $this->stringOrNull(
                $volumeInfo['title'] ?? null
            ),
            'author' => $this->formatAuthors(
                $volumeInfo['authors'] ?? null
            ),
            'published_date' => $this->normalizePublishedDate(
                $volumeInfo['publishedDate'] ?? null
            ),
            'description' => $this->stringOrNull(
                $volumeInfo['description'] ?? null
            ),
            'image_url' => $this->stringOrNull(
                $volumeInfo['imageLinks']['thumbnail'] ?? null
            ),
        ];
    }

    private function formatAuthors(mixed $authors): ?string
    {
        if (! is_array($authors)) {
            return null;
        }

        $authors = array_values(array_filter(
            $authors,
            fn (mixed $author): bool => is_string($author)
                && $author !== ''
        ));

        return $authors === []
            ? null
            : implode('、', $authors);
    }

    private function normalizePublishedDate(mixed $date): ?string
    {
        if (! is_string($date)) {
            return null;
        }

        $matched = preg_match(
            '/\A(\d{4})-(\d{2})-(\d{2})\z/',
            $date,
            $matches
        );

        if ($matched !== 1) {
            return null;
        }

        $year = (int) $matches[1];
        $month = (int) $matches[2];
        $day = (int) $matches[3];

        if (! checkdate($month, $day, $year)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== ''
            ? $value
            : null;
    }
}
