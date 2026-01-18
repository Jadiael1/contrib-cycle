<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ParticipantLoginRequest;
use App\Http\Requests\Api\V1\ParticipantRegisterRequest;
use App\Models\User;
use OpenApi\Attributes as OA;

class ParticipantAuthController extends Controller
{
    #[OA\Post(
        path: '/api/v1/auth/participant/register',
        tags: ['Auth'],
        summary: 'Participant registration',
        description: 'Registers a participant account.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ParticipantRegisterRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Participant registered.',
                content: new OA\JsonContent(ref: '#/components/schemas/ParticipantRegisterResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error.',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            ),
        ]
    )]
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

    #[OA\Post(
        path: '/api/v1/auth/participant/login',
        tags: ['Auth'],
        summary: 'Participant login',
        description: 'Authenticates a participant and returns a bearer token.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ParticipantLoginRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token issued.',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthTokenResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Incorrect username or password.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error.',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            ),
        ]
    )]
    public function login(ParticipantLoginRequest $request)
    {
        $data = $request->validated();

        $user = User::query()
            ->where('role', 'participant')
            ->where('phone', $data['phone'])
            ->first();

        if (!$user) {
            return response()->json(['message' => 'Incorrect username or password.'], 404);
        }

        $user->tokens()->delete();

        $token = $user->createToken('participant', ['participant'])->plainTextToken;

        return response()->json(['token' => $token]);
    }
}
