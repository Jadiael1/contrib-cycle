<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CollectiveProject;
use App\Models\ProjectMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectMembershipController extends Controller
{
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
