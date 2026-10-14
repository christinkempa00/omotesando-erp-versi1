<?php

namespace App\Http\Controllers\Outlet;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OutletController extends Controller
{
    public function dashboard(Request $request): View
    {
        // outlet.branch middleware sudah menjamin user->branch ada di
        // titik ini — lihat EnsureOutletHasBranch.
        $branch = $request->user()->scopingBranch();

        return view('outlet.dashboard', [
            'branch' => $branch,
        ]);
    }
}
