<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\RoleHomeResolver;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->redirectVerified($request);
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return $this->redirectVerified($request);
    }

    /**
     * BUKAN ->intended(route('dashboard')) — lihat RedirectsToRoleHome.
     * route('dashboard') sendiri di-gate role:GA,Admin (403 utk IT/Head).
     */
    private function redirectVerified(EmailVerificationRequest $request): RedirectResponse
    {
        return redirect()->route(RoleHomeResolver::routeNameFor($request->user()), ['verified' => 1]);
    }
}
