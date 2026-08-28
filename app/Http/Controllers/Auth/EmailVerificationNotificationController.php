<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Concerns\RedirectsToRoleHome;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    use RedirectsToRoleHome;

    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            // BUKAN ->intended(route('dashboard')) — lihat RedirectsToRoleHome.
            return $this->redirectToRoleHome($request->user());
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
