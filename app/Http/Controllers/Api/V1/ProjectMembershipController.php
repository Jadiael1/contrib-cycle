<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CollectiveProject;
use App\Models\ProjectMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class ProjectMembershipController extends Controller
{
    #[OA\Get(
        path: '/api/v1/projects/{project}/membership',
        tags: ['Participant Projects'],
        summary: 'Get membership status',
        description: 'Returns the membership status for the authenticated participant.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'project',
                in: 'path',
                required: true,
                description: 'Project slug.',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Membership status.',
                content: new OA\JsonContent(ref: '#/components/schemas/ProjectMembershipStatus')
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Project not found.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function show(Request $request, CollectiveProject $project)
    {
        $user = $request->user();

        $membership = ProjectMembership::query()
            ->where('collective_project_id', $project->id)
            ->where('user_id', $user->id)
            ->first();

        return response()->json([
            'is_member' => (bool) $membership && $membership->status === 'accepted',
            'status' => $membership?->status,
            'accepted_at' => $membership?->accepted_at,
            'removed_at' => $membership?->removed_at,
        ]);
    }

    #[OA\Post(
        path: '/api/v1/projects/{project}/join',
        tags: ['Participant Projects'],
        summary: 'Join a project',
        description: 'Confirms participation for the authenticated participant.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'project',
                in: 'path',
                required: true,
                description: 'Project slug.',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Participation confirmed.',
                content: new OA\JsonContent(ref: '#/components/schemas/ProjectMembershipJoinResponse')
            ),
            new OA\Response(
                response: 200,
                description: 'Already participating.',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')
            ),
            new OA\Response(
                response: 403,
                description: 'Participant removed.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 409,
                description: 'Project is full.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Project not found.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function join(Request $request, CollectiveProject $project)
    {
        $user = $request->user();

        return DB::transaction(function () use ($project, $user) {
            // lock project row to reduce race
            $projectLocked = CollectiveProject::query()
                ->whereKey($project->id)
                ->lockForUpdate()
                ->firstOrFail();

            $membership = ProjectMembership::query()
                ->where('collective_project_id', $projectLocked->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($membership && $membership->status === 'removed') {
                return response()->json(['message' => 'You were removed from this project.'], 403);
            }

            if ($membership && $membership->status === 'accepted') {
                return response()->json(['message' => 'Already participating.']);
            }

            $acceptedCount = ProjectMembership::query()
                ->where('collective_project_id', $projectLocked->id)
                ->where('status', 'accepted')
                ->lockForUpdate()
                ->count();

            if ($acceptedCount >= $projectLocked->participant_limit) {
                return response()->json(['message' => 'Project is full.'], 409);
            }

            $membership = ProjectMembership::updateOrCreate(
                ['collective_project_id' => $projectLocked->id, 'user_id' => $user->id],
                [
                    'status' => 'accepted',
                    'accepted_at' => now(),
                    'removed_at' => null,
                    'removed_by_user_id' => null,
                ]
            );

            return response()->json([
                'message' => 'Participation confirmed.',
                'membership_id' => $membership->id,
            ], 201);
        });
    }
}
