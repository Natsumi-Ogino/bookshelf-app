<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexBookRequest;
use App\Models\Book;
use App\Services\BookSearchService;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookCsvExportController extends Controller
{
    /**
     * 検索条件に一致する全書籍をUTF-8 BOM付きCSVで出力します。
     *
     * @param  IndexBookRequest  $request  検証済みの一覧表示条件を含むリクエスト
     * @param  BookSearchService  $bookSearch  書籍の検索・並び順を適用するサービス
     */
    public function __invoke(
        IndexBookRequest $request,
        BookSearchService $bookSearch
    ): StreamedResponse {
        $filters = $request->validated();
        $fileName = 'books_'.now('Asia/Tokyo')->format('Ymd_His').'.csv';

        return response()->streamDownload(
            function () use ($bookSearch, $filters): void {
                $output = fopen('php://output', 'wb');

                if ($output === false) {
                    throw new RuntimeException('CSV output stream could not be opened.');
                }

                fwrite($output, "\xEF\xBB\xBF");
                $this->writeRow($output, [
                    'タイトル',
                    '著者',
                    'ISBN',
                    '出版日',
                    'ジャンル',
                    '平均評価',
                    'レビュー件数',
                ]);

                foreach ($bookSearch->query($filters)->lazy(500) as $book) {
                    $this->writeBook($output, $book);
                }

                fclose($output);
            },
            $fileName,
            ['Content-Type' => 'text/csv; charset=UTF-8']
        );
    }

    /** @param  resource  $output */
    private function writeBook($output, Book $book): void
    {
        $this->writeRow($output, [
            $this->escapeSpreadsheetFormula($book->title),
            $this->escapeSpreadsheetFormula($book->author),
            $book->isbn ?? '',
            $book->published_date?->format('Y-m-d') ?? '',
            $this->escapeSpreadsheetFormula(
                $book->genres->pluck('name')->implode('、')
            ),
            $book->reviews_avg_rating === null
                ? ''
                : number_format((float) $book->reviews_avg_rating, 1, '.', ''),
            (string) $book->reviews_count,
        ]);
    }

    /**
     * @param  resource  $output
     * @param  list<string>  $fields
     */
    private function writeRow($output, array $fields): void
    {
        fputcsv($output, $fields, ',', '"', '', "\r\n");
    }

    private function escapeSpreadsheetFormula(string $value): string
    {
        return preg_match('/\A[\x00-\x20]*[=+\-@]/u', $value) === 1
            ? "'{$value}"
            : $value;
    }
}
