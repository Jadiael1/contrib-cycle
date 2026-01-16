<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\GeneratePaymentStatusReportRequest;
use App\Http\Resources\Api\V1\Admin\CollectiveProjectReportResource;
use App\Models\CollectiveProject;
use App\Models\CollectiveProjectReport;
use App\Jobs\GenerateCollectiveProjectPaymentStatusReportJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CollectiveProjectReportsController extends Controller
{
    public function index(Request $request, CollectiveProject $project)
    {
        $perPage = (int) $request->query('per_page', 20);

        $reports = CollectiveProjectReport::query()
            ->where('collective_project_id', $project->id)
            ->where('type', 'payment_status')
            ->orderByDesc('id')
            ->paginate($perPage);

        return CollectiveProjectReportResource::collection($reports);
    }

    public function store(GeneratePaymentStatusReportRequest $request, CollectiveProject $project)
    {
        $data = $request->validated();

        $report = CollectiveProjectReport::create([
            'collective_project_id' => $project->id,
            'created_by_user_id' => $request->user()?->id,
            'type' => 'payment_status',
            'status' => 'pending',
            'filters' => [
                'year' => (int) $data['year'],
                'month' => $data['month'] ?? null,
                'week_of_month' => $data['week_of_month'] ?? null,
                'status_scope' => $data['status_scope'] ?? 'accepted_only',
                'disk' => 'local',
            ],
        ]);

        GenerateCollectiveProjectPaymentStatusReportJob::dispatch($report->id);

        return (new CollectiveProjectReportResource($report))
            ->response()
            ->setStatusCode(202);
    }

    public function download(Request $request, CollectiveProject $project, CollectiveProjectReport $report)
    {
        if ($report->collective_project_id !== $project->id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        if ($report->status !== 'ready' || !$report->path) {
            return response()->json(['message' => 'Report not ready.'], 409);
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($report->disk);

        // Storage::download é o caminho padrão :contentReference[oaicite:5]{index=5}
        return $disk->download(
            $report->path,
            $report->file_name ?? basename($report->path)
        );
    }
}
