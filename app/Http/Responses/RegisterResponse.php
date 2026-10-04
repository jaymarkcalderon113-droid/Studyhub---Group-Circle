<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

/** After sign up, send the new user to their dashboard (always a student). */
class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return response()->json('', 201);
        }

        return redirect($request->user()->role->homePath());
    }
}
