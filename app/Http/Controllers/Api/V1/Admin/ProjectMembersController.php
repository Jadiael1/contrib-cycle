<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\ListProjectMembersRequest;
use App\Http\Resources\Api\V1\Admin\ProjectMemberResource;
use App\Models\CollectiveProject;
use App\Models\ProjectMembership;
use App\Models\User;
use Illuminate\Http\Request;

class ProjectMembersController extends Controller
{
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
