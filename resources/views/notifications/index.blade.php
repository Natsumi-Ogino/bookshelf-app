<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">通知一覧</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @forelse ($notifications as $notification)
                        <div class="border-b border-gray-200 py-5 last:border-b-0 {{ $notification->read_at === null ? 'bg-blue-50' : '' }}">
                            <div class="flex flex-col gap-3 px-4 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <h3 class="font-bold">{{ $notification->data['title'] }}</h3>
                                    <p class="mt-1 text-gray-700">{{ $notification->data['body'] }}</p>
                                    <p class="mt-2 text-sm text-gray-500">{{ $notification->created_at->timezone('Asia/Tokyo')->format('Y/m/d H:i') }}</p>
                                </div>

                                @if ($notification->read_at === null)
                                    <form action="{{ route('notifications.read', $notification) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="rounded bg-blue-500 px-4 py-2 font-bold text-white hover:bg-blue-700">
                                            既読にする
                                        </button>
                                    </form>
                                @else
                                    <span class="text-sm text-gray-500">既読</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500">通知はありません。</p>
                    @endforelse

                    @if ($notifications->hasPages())
                        <div class="mt-6">
                            {{ $notifications->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
