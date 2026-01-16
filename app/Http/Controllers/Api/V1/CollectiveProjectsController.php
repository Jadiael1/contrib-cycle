<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CollectiveProjectResource;
use App\Models\CollectiveProject;
use App\Models\ProjectMembership;
use Illuminate\Http\Request;

class CollectiveProjectsController extends Controller
{
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
