<x-app-layout sidebar="it">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-ink leading-tight">Manajemen User</h2>
            <a href="{{ route('it.users.create') }}"
               class="inline-flex items-center btn-primary">
                Buat User
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

            <div class="card-flat overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-hairline text-sm">
                        <thead class="border-b border-hairline">
                            <tr>
                                <th class="px-4 py-3 text-center text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Nama</th>
                                <th class="px-4 py-3 text-center text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Email</th>
                                <th class="px-4 py-3 text-center text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Role</th>
                                <th class="px-4 py-3 text-center text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Branch</th>
                                <th class="px-4 py-3 text-center text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Status</th>
                                <th class="px-4 py-3 text-center text-[11px] font-semibold tracking-[0.08em] text-ink-muted uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-hairline">
                            @forelse ($users as $user)
                                <tr class="hover:bg-[#FDFBF4]">
                                    <td class="px-4 py-3.5 font-medium text-ink">{{ $user->name }}</td>
                                    <td class="px-4 py-3.5 text-ink-muted">{{ $user->email }}</td>
                                    <td class="px-4 py-3.5">
                                        <div class="flex flex-wrap gap-1">
                                            @forelse ($user->roles as $role)
                                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium border border-hairline bg-white text-ink-muted">{{ $role->name }}</span>
                                            @empty
                                                <span class="text-xs text-ink-muted">-</span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td class="px-4 py-3.5 text-ink-muted">{{ $user->branch?->name ?? '-' }}</td>
                                    <td class="px-4 py-3.5">
                                        @if ($user->is_active)
                                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Aktif</span>
                                        @else
                                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            <a href="{{ route('it.users.edit', $user) }}" title="Edit" class="text-action hover:text-action-hover">
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
                                    <td colspan="6" class="px-4 py-8 text-center text-ink-muted">Belum ada user.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
