<div class="bg-gradient-to-br from-indigo-50 to-blue-50 border border-indigo-100 shadow-sm sm:rounded-lg mb-6">
    <div class="p-6">
        <div class="flex items-center gap-2 mb-3">
            <svg xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5 text-indigo-600"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            <h3 class="font-semibold text-base text-gray-800">
                ISBNから書籍情報を自動入力
            </h3>
        </div>

        <p class="text-xs text-gray-600 mb-3">
            13桁のISBNを入力すると、Google Books APIから書籍情報を取得してフォームを自動補完します。
        </p>

        <div class="flex items-stretch gap-2 w-full">
            <input
                type="text"
                id="isbn-search"
                maxlength="13"
                inputmode="numeric"
                autocomplete="off"
                placeholder="例: 9784101010014"
                class="w-full flex-1 min-w-0 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">

            <button
                type="button"
                id="fetch-btn"
                class="shrink-0 inline-flex items-center gap-1 bg-blue-500 hover:bg-blue-700 disabled:bg-blue-300 text-white text-sm font-bold py-2 px-4 rounded shadow-sm transition whitespace-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg"
                    class="h-4 w-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z" />
                </svg>
                <span id="fetch-btn-label">検索</span>
            </button>
        </div>

        <p
            id="fetch-error"
            class="mt-2 text-sm text-red-600 hidden"
            role="alert"></p>

        <p
            id="fetch-success"
            class="mt-2 text-sm text-green-600 hidden"
            role="status"></p>
    </div>
</div>

@push('scripts')
    <script>
        const isbnSearchInput = document.getElementById('isbn-search');
        const fetchButton = document.getElementById('fetch-btn');
        const fetchButtonLabel = document.getElementById('fetch-btn-label');
        const fetchError = document.getElementById('fetch-error');
        const fetchSuccess = document.getElementById('fetch-success');
        const lookupUrlTemplate = @json(
            route('books.isbn.lookup', ['isbn' => '__ISBN__'])
        );

        const lookupBookByIsbn = async () => {
            const isbn = isbnSearchInput.value.trim();

            fetchError.classList.add('hidden');
            fetchSuccess.classList.add('hidden');

            if (!/^\d{13}$/.test(isbn)) {
                fetchError.textContent = 'ISBNは13桁で入力してください。';
                fetchError.classList.remove('hidden');

                return;
            }

            fetchButton.disabled = true;
            fetchButtonLabel.textContent = '検索中...';

            try {
                const lookupUrl = lookupUrlTemplate.replace(
                    '__ISBN__',
                    encodeURIComponent(isbn)
                );

                const response = await fetch(lookupUrl, {
                    headers: {
                        Accept: 'application/json',
                    },
                });

                const data = await response.json();

                if (!response.ok) {
                    fetchError.textContent = data.error
                        || 'API通信エラーが発生しました。';
                    fetchError.classList.remove('hidden');

                    return;
                }

                document.getElementById('title').value = data.title || '';
                document.getElementById('author').value = data.author || '';
                document.getElementById('isbn').value = isbn;
                document.getElementById('published_date').value
                    = data.published_date || '';
                document.getElementById('description').value
                    = data.description || '';
                document.getElementById('image_url').value
                    = data.image_url || '';

                fetchSuccess.textContent = '書籍情報を取得しました。';
                fetchSuccess.classList.remove('hidden');
            } catch (error) {
                fetchError.textContent = '通信エラーが発生しました。';
                fetchError.classList.remove('hidden');
            } finally {
                fetchButton.disabled = false;
                fetchButtonLabel.textContent = '検索';
            }
        };

        fetchButton.addEventListener('click', lookupBookByIsbn);

        isbnSearchInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                lookupBookByIsbn();
            }
        });
    </script>
@endpush
