<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">読書計画編集</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6 rounded bg-gray-50 p-4">
                        <p class="font-bold">{{ $readingPlan->book->title }}</p>
                        <p class="text-sm text-gray-600">{{ $readingPlan->book->author }}</p>
                    </div>

                    <form action="{{ route('reading-plans.update', $readingPlan) }}" method="POST" novalidate>
                        @csrf
                        @method('PUT')

                        <div class="mb-5">
                            <label for="target_date" class="block text-sm font-medium text-gray-700">期日 <span class="text-red-500">*</span></label>
                            <input type="date" name="target_date" id="target_date" value="{{ old('target_date', $readingPlan->target_date->toDateString()) }}" min="{{ today()->toDateString() }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('target_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-end">
                            <a href="{{ route('reading-plans.index') }}" class="mr-4 text-gray-600 hover:text-gray-900">キャンセル</a>
                            <button type="submit" class="rounded bg-blue-500 px-4 py-2 font-bold text-white hover:bg-blue-700">更新</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
