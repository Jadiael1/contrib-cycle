<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\ListProjectMembersRequest;
use App\Http\Resources\Api\V1\Admin\ProjectMemberResource;
use App\Models\CollectiveProject;
use App\Models\ProjectMembership;
use App\Models\User;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ProjectMembersController extends Controller
{
    #[OA\Get(
        path: '/api/v1/admin/projects/{project}/members',
        tags: ['Admin Members'],
        summary: 'List project members',
        description: 'Returns a paginated list of members for a project.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'project',
                in: 'path',
                required: true,
                description: 'Project ID.',
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
            new OA\Parameter(
                name: 'status',
                in: 'query',
                required: false,
                description: 'Filter by membership status.',
                schema: new OA\Schema(type: 'string', enum: ['pending', 'accepted', 'removed'])
            ),
            new OA\Parameter(
                name: 'q',
                in: 'query',
                required: false,
                description: 'Search by phone or name.',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                description: 'Items per page (1-100).',
                schema: new OA\Schema(type: 'integer', example: 20)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated members.',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedProjectMembersResponse')
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
    public function index(ListProjectMembersRequest $request, CollectiveProject $project)
    {
        $data = $request->validated();

        $status = $data['status'] ?? null;
        $q = $data['q'] ?? null;
        $perPage = $data['per_page'] ?? 20;

        $query = ProjectMembership::query()
            ->with('user')
            ->where('collective_project_id', $project->id);

        if ($status) {
            $query->where('status', $status);
        }

        if ($q) {
            $query->whereHas('user', function ($u) use ($q) {
                $u->where('phone', 'like', "%{$q}%")
                    ->orWhere('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%");
            });
        }

        $memberships = $query
            ->orderByDesc('accepted_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        return ProjectMemberResource::collection($memberships);
    }

    #[OA\Delete(
        path: '/api/v1/admin/projects/{project}/members/{user}',
        tags: ['Admin Members'],
        summary: 'Remove project member',
        description: 'Marks a member as removed from the project.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'project',
                in: 'path',
                required: true,
                description: 'Project ID.',
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
            new OA\Parameter(
                name: 'user',
                in: 'path',
                required: true,
                description: 'User ID.',
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Member removed.',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Membership not found.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function remove(Request $request, CollectiveProject $project, User $user)
    {
        $membership = ProjectMembership::query()
            ->where('collective_project_id', $project->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $membership) {
            return response()->json(['message' => 'Membership not found.'], 404);
        }

        $membership->update([
            'status' => 'removed',
            'removed_at' => now(),
            'removed_by_user_id' => $request->user()->id,
        ]);

        return response()->json(['message' => 'Member removed.']);
    }
}
