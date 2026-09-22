<x-app-layout>
    <x-slot name="header">
        <h1 class="font-semibold text-xl text-gray-800 leading-tight">
            403 Forbidden
        </h1>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="mb-6">この操作を行う権限がありません。</p>
                    <a href="{{ route('books.index') }}" class="text-blue-600 underline hover:text-blue-800">
                        書籍一覧へ戻る
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
