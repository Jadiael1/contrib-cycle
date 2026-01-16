<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreCollectiveProjectPaymentMethodRequest;
use App\Http\Requests\Api\V1\Admin\UpdateCollectiveProjectPaymentMethodRequest;
use App\Http\Resources\Api\V1\CollectiveProjectPaymentMethodResource;
use App\Models\CollectiveProject;
use App\Models\CollectiveProjectPaymentMethod;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CollectiveProjectPaymentMethodsController extends Controller
{
    #[OA\Get(
        path: '/api/v1/admin/projects/{project}/payment-methods',
        tags: ['Admin Payment Methods'],
        summary: 'List payment methods',
        description: 'Returns payment methods for a project.',
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
                description: 'Payment method list.',
                content: new OA\JsonContent(
                    type: 'object',
                    required: ['data'],
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/CollectiveProjectPaymentMethodResource')
                        ),
                    ]
                )
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
    public function index(CollectiveProject $project)
    {
        $methods = $project->paymentMethods()->orderBy('sort_order')->get();

        return CollectiveProjectPaymentMethodResource::collection($methods);
    }

    #[OA\Post(
        path: '/api/v1/admin/projects/{project}/payment-methods',
        tags: ['Admin Payment Methods'],
        summary: 'Create payment method',
        description: 'Adds a payment method to the project.',
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
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreCollectiveProjectPaymentMethodRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Payment method created.',
                content: new OA\JsonContent(
                    type: 'object',
                    required: ['data'],
                    properties: [
                        new OA\Property(
                            property: 'data',
                            ref: '#/components/schemas/CollectiveProjectPaymentMethodResource'
                        ),
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
            new OA\Response(
                response: 404,
                description: 'Project not found.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function store(StoreCollectiveProjectPaymentMethodRequest $request, CollectiveProject $project)
    {
        $data = $request->validated();

        $nextSortOrder = ((int) $project->paymentMethods()->max('sort_order')) + 1;
        $sortOrder = (int) ($data['sort_order'] ?? $nextSortOrder);

        $method = $project->paymentMethods()->create([
            'payment_method_type' => $data['payment_method_type'],
            'payment_method_payload' => $data['payment_method_payload'],
            'label' => $data['label'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'sort_order' => $sortOrder,
        ]);

        return (new CollectiveProjectPaymentMethodResource($method))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Patch(
        path: '/api/v1/admin/projects/{project}/payment-methods/{paymentMethod}',
        tags: ['Admin Payment Methods'],
        summary: 'Update payment method',
        description: 'Updates a payment method for the project.',
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
                name: 'paymentMethod',
                in: 'path',
                required: true,
                description: 'Payment method ID.',
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateCollectiveProjectPaymentMethodRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Payment method updated.',
                content: new OA\JsonContent(
                    type: 'object',
                    required: ['data'],
                    properties: [
                        new OA\Property(
                            property: 'data',
                            ref: '#/components/schemas/CollectiveProjectPaymentMethodResource'
                        ),
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
            new OA\Response(
                response: 404,
                description: 'Payment method not found.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function update(
        UpdateCollectiveProjectPaymentMethodRequest $request,
        CollectiveProject $project,
        CollectiveProjectPaymentMethod $paymentMethod
    ) {
        $data = $request->safe()->only([
            'payment_method_type',
            'payment_method_payload',
            'label',
            'is_active',
            'sort_order',
        ]);

        $paymentMethod->update($data);

        return new CollectiveProjectPaymentMethodResource($paymentMethod->refresh());
    }

    #[OA\Delete(
        path: '/api/v1/admin/projects/{project}/payment-methods/{paymentMethod}',
        tags: ['Admin Payment Methods'],
        summary: 'Deactivate payment method',
        description: 'Deactivates a payment method. The project must keep at least one active method.',
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
                name: 'paymentMethod',
                in: 'path',
                required: true,
                description: 'Payment method ID.',
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Payment method deactivated.',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Cannot deactivate last active method.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Payment method not found.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function destroy(Request $request, CollectiveProject $project, CollectiveProjectPaymentMethod $paymentMethod)
    {
        // regra simples: projeto precisa ter pelo menos 1 método ativo
        if ($paymentMethod->is_active) {
            $activeCount = $project->paymentMethods()->where('is_active', true)->count();

            if ($activeCount <= 1) {
                return response()->json([
                    'message' => 'Project must have at least one active payment method.',
                ], 422);
            }
        }

        $paymentMethod->update(['is_active' => false]);

        return response()->json(['message' => 'Payment method deactivated.']);
    }
}
