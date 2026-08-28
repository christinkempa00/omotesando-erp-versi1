<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Concerns\RedirectsToRoleHome;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    use RedirectsToRoleHome;

    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        // BUKAN ->intended(route('dashboard')) — lihat RedirectsToRoleHome.
        return $request->user()->hasVerifiedEmail()
                    ? $this->redirectToRoleHome($request->user())
                    : view('auth.verify-email');
    }
}
