<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreCollectiveProjectPaymentMethodRequest;
use App\Http\Requests\Api\V1\Admin\UpdateCollectiveProjectPaymentMethodRequest;
use App\Http\Resources\Api\V1\CollectiveProjectPaymentMethodResource;
use App\Models\CollectiveProject;
use App\Models\CollectiveProjectPaymentMethod;
use Illuminate\Http\Request;

class CollectiveProjectPaymentMethodsController extends Controller
{
    public function index(CollectiveProject $project)
    {
        $methods = $project->paymentMethods()->orderBy('sort_order')->get();

        return CollectiveProjectPaymentMethodResource::collection($methods);
    }

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
