<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CollectiveProjectResource;
use App\Models\CollectiveProject;
use App\Models\ProjectMembership;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CollectiveProjectsController extends Controller
{
    #[OA\Get(
        path: '/api/v1/projects/{project}',
        tags: ['Participant Projects'],
        summary: 'Get project details',
        description: 'Returns project details plus membership and stats for the authenticated participant.',
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
                description: 'Project details with membership info.',
                content: new OA\JsonContent(ref: '#/components/schemas/CollectiveProjectDetailResponse')
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
        /** @var \App\Models\User $user */
        $user = $request->user();

        $membership = ProjectMembership::query()
            ->where('collective_project_id', $project->id)
            ->where('user_id', $user->id)
            ->first();

        $canSeePayload = $membership?->status === 'accepted';

        $project->load([
            'paymentMethods' => function ($q) use ($canSeePayload) {
                $q->where('is_active', true)->orderBy('sort_order');

                if (! $canSeePayload) {
                    $q->select([
                        'id',
                        'collective_project_id',
                        'payment_method_type',
                        'label',
                        'sort_order',
                        'is_active',
                    ]);
                }
            },
        ]);

        $project->loadCount([
            'memberships as accepted_count' => fn($q) => $q->where('status', 'accepted'),
        ]);

        $acceptedCount = (int) ($project->accepted_count ?? 0);

        return (new CollectiveProjectResource($project))->additional([
            'membership' => [
                'status' => $membership?->status,
                'accepted_at' => $membership?->accepted_at?->toISOString(),
                'removed_at' => $membership?->removed_at?->toISOString(),
                'blocked' => $membership?->status === 'removed',
            ],
            'stats' => [
                'accepted_count' => $acceptedCount,
                'available_slots' => max(0, $project->participant_limit - $acceptedCount),
                'is_full' => $acceptedCount >= $project->participant_limit,
            ],
        ]);
    }
}
