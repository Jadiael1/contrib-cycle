<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreCollectiveProjectRequest;
use App\Http\Resources\Api\V1\CollectiveProjectResource;
use App\Models\CollectiveProject;
use App\Models\CollectiveProjectPaymentMethod;
use App\Models\ProjectMembership;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class CollectiveProjectsController extends Controller
{
    #[OA\Get(
        path: '/api/v1/admin/projects/{project}',
        tags: ['Admin Projects'],
        summary: 'Get project details (admin)',
        description: 'Returns project details with counts and stats.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'project',
                in: 'path',
                required: true,
                description: 'Project ID.',
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Project details.',
                content: new OA\JsonContent(ref: '#/components/schemas/CollectiveProjectAdminDetailResponse')
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
    public function show(CollectiveProject $project)
    {
        $project->load(['paymentMethods' => fn($q) => $q->orderBy('sort_order')]);

        $counts = ProjectMembership::query()
            ->where('collective_project_id', $project->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $accepted = (int) ($counts['accepted'] ?? 0);

        return (new CollectiveProjectResource($project))->additional([
            'counts' => [
                'pending' => (int) ($counts['pending'] ?? 0),
                'accepted' => $accepted,
                'removed' => (int) ($counts['removed'] ?? 0),
            ],
            'stats' => [
                'available_slots' => max(0, $project->participant_limit - $accepted),
                'is_full' => $accepted >= $project->participant_limit,
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/v1/admin/projects',
        tags: ['Admin Projects'],
        summary: 'List projects (admin)',
        description: 'Returns all projects for administrators.',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Project list.',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/CollectiveProject')
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function index()
    {
        return response()->json(
            CollectiveProject::query()->orderByDesc('id')->get()
        );
    }

    #[OA\Post(
        path: '/api/v1/admin/projects',
        tags: ['Admin Projects'],
        summary: 'Create project',
        description: 'Creates a project with its initial payment method.',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreCollectiveProjectRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Project created.',
                content: new OA\JsonContent(
                    type: 'object',
                    required: ['data'],
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/CollectiveProjectResource'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error.',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function store(StoreCollectiveProjectRequest $request)
    {
        $data = $request->validated();

        $slugBase = Str::slug($data['title']);
        $slug = $slugBase;
        $i = 2;

        while (CollectiveProject::query()->where('slug', $slug)->exists()) {
            $slug = "{$slugBase}-{$i}";
            $i++;
        }

        return DB::transaction(function () use ($request, $data, $slug) {
            $project = CollectiveProject::create([
                'title' => $data['title'],
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'participant_limit' => $data['participant_limit'],
                'amount_per_participant' => $data['amount_per_participant'],
                'payment_interval' => $data['payment_interval'],
                'payments_per_interval' => $data['payments_per_interval'],
                'is_active' => true,
                'created_by_user_id' => $request->user()->id,
            ]);

            CollectiveProjectPaymentMethod::create([
                'collective_project_id' => $project->id,
                'payment_method_type' => $data['payment_method_type'],
                'payment_method_payload' => $data['payment_method_payload'], // array -> encrypted JSON string via cast
                'label' => 'Primary',
                'is_active' => true,
                'sort_order' => 1,
            ]);

            $project->load(['paymentMethods' => fn($q) => $q->orderBy('sort_order')]);

            return (new CollectiveProjectResource($project))
                ->response()
                ->setStatusCode(201);
        });
    }
}
