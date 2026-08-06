<?php

namespace App\Http\Controllers\IT;

use App\Http\Controllers\Controller;
use App\Models\IT\ItBoard;
use App\Models\IT\ItTask;
use App\Models\IT\ItTaskLabel;
use App\Models\SystemModule;
use App\Models\User;
use Illuminate\View\View;

/**
 * Papan Kerja Kanban tim IT (bug fix, pengembangan fitur, dst). Untuk saat
 * ini selalu menampilkan board pertama (default "Pengembangan Sistem ERP")
 * — struktur tabel sudah mendukung banyak board untuk pengembangan ke depan.
 */
class ItBoardController extends Controller
{
    public function index(): View
    {
        $board = ItBoard::with([
            'columns.tasks.assignee:id,name',
            'columns.tasks.relatedModule:id,name',
            'columns.tasks.labels:id,name,color',
            'columns.tasks.checklistItems',
            'columns.tasks.comments.user:id,name',
        ])->oldest('id')->firstOrFail();

        return view('it.board.index', [
            'board' => $board,
            'users' => User::orderBy('name')->get(['id', 'name']),
            'labels' => ItTaskLabel::orderBy('name')->get(),
            'systemModules' => SystemModule::orderBy('name')->get(['id', 'name']),
            'priorityLabels' => ItTask::priorityLabels(),
        ]);
    }
}
