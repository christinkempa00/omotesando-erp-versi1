@props(['name', 'options', 'selected' => null, 'label' => null, 'allLabel' => 'Semua'])

<div>
    @if ($label)
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">{{ $label }}</p>
    @endif
    <div class="flex flex-wrap gap-2">
        <label class="cursor-pointer">
            <input type="radio" name="{{ $name }}" value="" class="peer hidden" @checked(! $selected)>
            <span class="inline-block px-3 py-1.5 rounded-full text-sm bg-gray-100 text-gray-600 peer-checked:bg-gray-900 peer-checked:text-white transition">
                {{ $allLabel }}
            </span>
        </label>
        @foreach ($options as $value => $optionLabel)
            <label class="cursor-pointer">
                <input type="radio" name="{{ $name }}" value="{{ $value }}" class="peer hidden" @checked($selected === $value)>
                <span class="inline-block px-3 py-1.5 rounded-full text-sm bg-gray-100 text-gray-600 peer-checked:bg-gray-900 peer-checked:text-white transition">
                    {{ $optionLabel }}
                </span>
            </label>
        @endforeach
    </div>
</div>
