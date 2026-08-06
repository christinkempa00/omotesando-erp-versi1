<?php

namespace App\Http\Controllers\IT;

use App\Http\Controllers\Controller;
use App\Models\IT\ItTaskLabel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Katalog label yang tersedia untuk ditempel ke task (beda dengan
 * penempelan label ke task tertentu, yang dilakukan lewat
 * ItTaskController::update / label_ids dari modal detail task).
 */
class ItTaskLabelController extends Controller
{
    /**
     * Palet warna Tailwind yang dipakai badge label — dibatasi ke daftar ini
     * (bukan input bebas) supaya kelas warnanya selalu ada di CSS terkompilasi
     * (Tailwind JIT hanya generate kelas yang benar-benar muncul literal di
     * source, lihat resources/views/it/board/index.blade.php).
     */
    public const COLORS = ['red', 'orange', 'yellow', 'green', 'blue', 'purple', 'pink', 'gray'];

    public function index(): View
    {
        return view('it.labels.index', [
            'labels' => ItTaskLabel::withCount('tasks')->orderBy('name')->get(),
            'colors' => self::COLORS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:it_task_labels,name'],
            'color' => ['required', Rule::in(self::COLORS)],
        ]);

        ItTaskLabel::create($validated);

        return back()->with('success', "Label \"{$validated['name']}\" berhasil ditambahkan.");
    }
}
