@props(['name', 'label', 'selected' => null])

<div class="flex items-center justify-between gap-3 py-2">
    <span class="text-sm text-gray-700">{{ $label }} <span class="text-red-500">*</span></span>
    <div class="flex gap-2">
        <label class="cursor-pointer">
            <input type="radio" name="{{ $name }}" value="1" class="peer sr-only" required @checked($selected === true)>
            <span class="inline-block px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-600 peer-checked:bg-green-600 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-offset-1 peer-focus-visible:ring-action transition">
                Ya
            </span>
        </label>
        <label class="cursor-pointer">
            <input type="radio" name="{{ $name }}" value="0" class="peer sr-only" required @checked($selected === false)>
            <span class="inline-block px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-600 peer-checked:bg-red-600 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-offset-1 peer-focus-visible:ring-action transition">
                Tidak
            </span>
        </label>
    </div>
</div>
