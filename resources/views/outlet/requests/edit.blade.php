<x-app-layout sidebar="outlet">
    <x-slot name="header">
        <div>
            <x-back-link :href="route('outlet.requests.show', $gaRequest)" />
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Lanjutkan Draft {{ $gaRequest->request_number }}
            </h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm rounded-lg p-6">
                @if ($errors->any())
                    <div class="mb-5 rounded-md bg-red-50 border border-red-200 p-4 text-sm text-red-700">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @include('ga.requests._form', ['gaRequest' => $gaRequest, 'formAction' => route('outlet.requests.update', $gaRequest)])
            </div>
        </div>
    </div>
</x-app-layout>
