<x-app-layout>
    <x-slot name="header">
        <div>
            <x-back-link :href="route('ga.assets.index')" />
            <h2 class="font-semibold text-xl text-ink leading-tight">Tambah Aset Baru</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

            @if ($errors->any())
                <div class="mb-4 bg-red-50 border border-red-200 text-red-800 text-sm rounded-md px-4 py-3">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('ga.assets.store') }}" enctype="multipart/form-data">
                @csrf
                @include('ga.assets._form', ['submitLabel' => 'Simpan', 'cancelUrl' => route('ga.assets.index')])
            </form>
        </div>
    </div>
</x-app-layout>
