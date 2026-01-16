<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AdminLoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    public function login(AdminLoginRequest $request)
    {
        $data = $request->validated();

        $user = User::query()
            ->where('role', 'admin')
            ->where('username', $data['username'])
            ->first();

        if (!$user || !$user->password || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        // opcional: 1 token ativo por admin
        $user->tokens()->delete();

        $token = $user->createToken('admin', ['admin'])->plainTextToken;

        return response()->json(['token' => $token]);
    }
}
