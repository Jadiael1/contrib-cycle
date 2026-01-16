<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function logout(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();
        /** @var \Laravel\Sanctum\PersonalAccessToken|null $token */
        $token = $user?->currentAccessToken();

        $token?->delete();

        return response()->json(['message' => 'Logged out.']);
    }
}
