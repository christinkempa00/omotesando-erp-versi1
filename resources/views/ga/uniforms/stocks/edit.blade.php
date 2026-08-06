<x-app-layout>
    <x-slot name="header">
        <div>
            <x-back-link :href="route('ga.uniforms.stocks.show', $stock)" />
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Varian — {{ $stock->stock_code }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-lg p-6">

                @if ($errors->any())
                    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 text-sm rounded-md px-4 py-3">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('ga.uniforms.stocks.update', $stock) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    @include('ga.uniforms.stocks._edit-form')

                    <div class="flex justify-end gap-3 mt-6">
                        <a href="{{ route('ga.uniforms.stocks.show', $stock) }}" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Batal</a>
                        <button type="submit" class="px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
