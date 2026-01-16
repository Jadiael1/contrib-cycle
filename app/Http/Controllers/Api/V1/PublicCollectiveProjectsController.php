<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CollectiveProjectResource;
use App\Models\CollectiveProject;
use OpenApi\Attributes as OA;

class PublicCollectiveProjectsController extends Controller
{
    #[OA\Get(
        path: '/api/v1/projects',
        tags: ['Public Projects'],
        summary: 'List active projects',
        description: 'Returns active projects with public payment method data.',
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of active projects.',
                content: new OA\JsonContent(
                    type: 'object',
                    required: ['data'],
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/CollectiveProjectResource')
                        ),
                    ]
                )
            ),
        ]
    )]
    public function index()
    {
        $projects = CollectiveProject::query()
            ->where('is_active', true)
            ->orderByDesc('id')
            ->with([
                'paymentMethods' => fn($q) => $q
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    // NÃO selecionar payload aqui (public)
                    ->select([
                        'id',
                        'collective_project_id',
                        'payment_method_type',
                        'label',
                        'sort_order',
                        'is_active',
                    ]),
            ])
            ->get([
                'id',
                'title',
                'slug',
                'participant_limit',
                'amount_per_participant',
                'payment_interval',
                'payments_per_interval',
            ]);

        return CollectiveProjectResource::collection($projects);
    }
}
