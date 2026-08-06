<x-app-layout sidebar="finance">
    <x-slot name="header">
        <div>
            <x-back-link :href="route('finance.transaction-mappings.index')" />
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit Mapping — {{ $typeLabels[$mapping->transaction_type] ?? $mapping->transaction_type }}
            </h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-lg mx-auto sm:px-6 lg:px-8">
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

                <form method="POST" action="{{ route('finance.transaction-mappings.update', $mapping) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-5">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Akun Debit *</label>
                        <select name="debit_account_id" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}" @selected(old('debit_account_id', $mapping->debit_account_id) == $account->id)>
                                    {{ $account->code }} — {{ $account->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-5">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Akun Kredit *</label>
                        <select name="credit_account_id" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}" @selected(old('credit_account_id', $mapping->credit_account_id) == $account->id)>
                                    {{ $account->code }} — {{ $account->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
