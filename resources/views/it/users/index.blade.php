<x-app-layout sidebar="it">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Manajemen User</h2>
            <a href="{{ route('it.users.create') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-gold-500 to-gold-600 text-white text-sm font-medium rounded-lg shadow-[0_4px_12px_-2px_rgba(200,155,44,0.4)] hover:-translate-y-px hover:shadow-[0_6px_16px_-2px_rgba(200,155,44,0.5)] transition">
                + User Baru
            </a>
        </div>
    </x-slot>

    <div class="font-it py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('success'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-lg px-4 py-3">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg px-4 py-3">
                    {{ session('error') }}
                </div>
            @endif

            <div class="glass-panel overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <th class="px-4 py-3.5">Nama</th>
                                <th class="px-4 py-3.5">Email</th>
                                <th class="px-4 py-3.5">Role</th>
                                <th class="px-4 py-3.5">Branch</th>
                                <th class="px-4 py-3.5">Status</th>
                                <th class="px-4 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($users as $user)
                                <tr>
                                    <td class="px-4 py-3.5 font-medium text-gray-800">{{ $user->name }}</td>
                                    <td class="px-4 py-3.5 text-gray-600">{{ $user->email }}</td>
                                    <td class="px-4 py-3.5">
                                        <div class="flex flex-wrap gap-1">
                                            @forelse ($user->roles as $role)
                                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-gold-100 text-gold-700">{{ $role->name }}</span>
                                            @empty
                                                <span class="text-xs text-gray-400">-</span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td class="px-4 py-3.5 text-gray-600">{{ $user->branch?->name ?? '-' }}</td>
                                    <td class="px-4 py-3.5">
                                        @if ($user->is_active)
                                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Aktif</span>
                                        @else
                                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            <a href="{{ route('it.users.edit', $user) }}" title="Edit" class="text-gold-600 hover:text-gold-800">
                                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M11 4H4a1 1 0 0 0-1 1v15a1 1 0 0 0 1 1h15a1 1 0 0 0 1-1v-7"/>
                                                    <path d="M18.5 2.5a1.71 1.71 0 0 1 2 2L12 13l-4 1 1-4Z"/>
                                                </svg>
                                            </a>
                                            @if ($user->id !== auth()->id())
                                                <form method="POST" action="{{ route('it.users.destroy', $user) }}"
                                                      onsubmit="return confirm('Hapus akun {{ $user->name }}? Tindakan ini permanen.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" title="Hapus" class="text-red-600 hover:text-red-800">
                                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                                            <path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                                            <path d="M10 11v6M14 11v6"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">Belum ada user.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
