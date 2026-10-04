<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

/** Role-based redirect after a successful login. */
class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        // "intended" = the page they originally wanted; otherwise their role's home.
        return redirect()->intended($request->user()->role->homePath());
    }
}
