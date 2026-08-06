<?php

namespace App\Http\Controllers\IT;

use App\Http\Controllers\Controller;
use App\Models\IT\ItTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItTaskCommentController extends Controller
{
    public function store(Request $request, ItTask $itTask): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string'],
        ]);

        $comment = $itTask->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
        ]);

        return response()->json([
            'comment' => $comment->load('user:id,name'),
        ], 201);
    }
}
