<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    #[OA\Post(
        path: '/api/v1/auth/logout',
        tags: ['Auth'],
        summary: 'Logout',
        description: 'Revokes the current access token.',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logged out.',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
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
