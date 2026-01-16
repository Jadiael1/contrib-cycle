<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ParticipantLoginRequest;
use App\Http\Requests\Api\V1\ParticipantRegisterRequest;
use App\Models\User;

class ParticipantAuthController extends Controller
{
    public function register(ParticipantRegisterRequest $request)
    {
        $data = $request->validated();

        $user = User::create([
            'role' => 'participant',
            'phone' => $data['phone'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'password' => null,
            'username' => null,
        ]);

        return response()->json([
            'user_id' => $user->id,
            'message' => 'Registered. Now you can join a project and confirm participation.',
        ], 201);
    }

    public function login(ParticipantLoginRequest $request)
    {
        $data = $request->validated();

        $user = User::query()
            ->where('role', 'participant')
            ->where('phone', $data['phone'])
            ->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $user->tokens()->delete();

        $token = $user->createToken('participant', ['participant'])->plainTextToken;

        return response()->json(['token' => $token]);
    }
}
