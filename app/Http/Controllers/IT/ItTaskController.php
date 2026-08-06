<?php

namespace App\Http\Controllers\IT;

use App\Http\Controllers\Controller;
use App\Models\IT\ItTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRUD task Papan Kerja Kanban — semua endpoint JSON (dipanggil via
 * fetch dari it/board/index.blade.php), termasuk perpindahan kolom saat
 * drag & drop (lihat method update: menerima board_column_id + order baru).
 */
class ItTaskController extends Controller
{
    private const RELATIONS = [
        'assignee:id,name',
        'relatedModule:id,name',
        'labels:id,name,color',
        'checklistItems',
        'comments.user:id,name',
    ];

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'board_column_id' => ['required', 'exists:it_board_columns,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['nullable', 'in:'.implode(',', array_keys(ItTask::priorityLabels()))],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
            'related_module_id' => ['nullable', 'exists:system_modules,id'],
        ]);

        $nextOrder = ItTask::where('board_column_id', $validated['board_column_id'])->max('order') + 1;

        $task = ItTask::create([
            ...$validated,
            'priority' => $validated['priority'] ?? ItTask::PRIORITY_MEDIUM,
            'order' => $nextOrder,
        ]);

        return response()->json([
            'task' => $task->load(self::RELATIONS),
        ], 201);
    }

    public function show(ItTask $itTask): JsonResponse
    {
        return response()->json([
            'task' => $itTask->load(self::RELATIONS),
        ]);
    }

    public function update(Request $request, ItTask $itTask): JsonResponse
    {
        $validated = $request->validate([
            'board_column_id' => ['sometimes', 'exists:it_board_columns,id'],
            'order' => ['sometimes', 'integer', 'min:0'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'priority' => ['sometimes', 'in:'.implode(',', array_keys(ItTask::priorityLabels()))],
            'assignee_id' => ['sometimes', 'nullable', 'exists:users,id'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'related_module_id' => ['sometimes', 'nullable', 'exists:system_modules,id'],
            'label_ids' => ['sometimes', 'array'],
            'label_ids.*' => ['integer', 'exists:it_task_labels,id'],
        ]);

        if (array_key_exists('label_ids', $validated)) {
            $itTask->labels()->sync($validated['label_ids']);
            unset($validated['label_ids']);
        }

        $itTask->fill($validated);
        $itTask->save();

        return response()->json([
            'task' => $itTask->load(self::RELATIONS),
        ]);
    }

    public function destroy(ItTask $itTask): JsonResponse
    {
        $itTask->delete();

        return response()->json(['message' => 'Task dihapus.']);
    }
}
