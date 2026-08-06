<?php

namespace App\Http\Controllers\IT;

use App\Http\Controllers\Controller;
use App\Models\IT\ItTask;
use App\Models\IT\ItTaskChecklistItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItTaskChecklistController extends Controller
{
    public function store(Request $request, ItTask $itTask): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:255'],
        ]);

        $nextOrder = $itTask->checklistItems()->max('order') + 1;

        $item = $itTask->checklistItems()->create([
            'content' => $validated['content'],
            'order' => $nextOrder,
        ]);

        return response()->json(['item' => $item], 201);
    }

    public function update(Request $request, ItTask $itTask, ItTaskChecklistItem $item): JsonResponse
    {
        abort_if($item->task_id !== $itTask->id, 404);

        $validated = $request->validate([
            'content' => ['sometimes', 'string', 'max:255'],
            'is_done' => ['sometimes', 'boolean'],
        ]);

        $item->fill($validated);
        $item->save();

        return response()->json(['item' => $item]);
    }

    public function destroy(ItTask $itTask, ItTaskChecklistItem $item): JsonResponse
    {
        abort_if($item->task_id !== $itTask->id, 404);

        $item->delete();

        return response()->json(['message' => 'Item checklist dihapus.']);
    }
}
