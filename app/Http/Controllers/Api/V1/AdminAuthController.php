<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AdminLoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

class AdminAuthController extends Controller
{
    #[OA\Post(
        path: '/api/v1/auth/admin/login',
        tags: ['Auth'],
        summary: 'Admin login',
        description: 'Authenticates an admin and returns a bearer token.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AdminLoginRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token issued.',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthTokenResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'Invalid credentials.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error.',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            ),
        ],
    )]
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
