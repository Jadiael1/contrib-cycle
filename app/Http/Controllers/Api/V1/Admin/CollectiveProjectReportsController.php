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
use OpenApi\Attributes as OA;

class CollectiveProjectReportsController extends Controller
{
    #[OA\Get(
        path: '/api/v1/admin/projects/{project}/reports',
        tags: ['Admin Reports'],
        summary: 'List reports',
        description: 'Returns a paginated list of reports for the project.',
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
                name: 'per_page',
                in: 'query',
                required: false,
                description: 'Items per page.',
                schema: new OA\Schema(type: 'integer', example: 20)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated reports.',
                content: new OA\JsonContent(ref: '#/components/schemas/PaginatedReportsResponse')
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

    #[OA\Post(
        path: '/api/v1/admin/projects/{project}/reports/payment-status',
        tags: ['Admin Reports'],
        summary: 'Generate payment status report',
        description: 'Queues a payment status report for generation.',
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
            content: new OA\JsonContent(ref: '#/components/schemas/GeneratePaymentStatusReportRequest')
        ),
        responses: [
            new OA\Response(
                response: 202,
                description: 'Report queued.',
                content: new OA\JsonContent(
                    type: 'object',
                    required: ['data'],
                    properties: [
                        new OA\Property(
                            property: 'data',
                            ref: '#/components/schemas/CollectiveProjectReportResource'
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
    public function store(GeneratePaymentStatusReportRequest $request, CollectiveProject $project)
    {
        $data = $request->validated();

        $report = CollectiveProjectReport::create([
            'collective_project_id' => $project->id,
            'created_by_user_id' => $request->user()?->id,
            'report_type' => 'payment_status',
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

    #[OA\Get(
        path: '/api/v1/admin/projects/{project}/reports/{report}/download',
        tags: ['Admin Reports'],
        summary: 'Download report file',
        description: 'Downloads the generated report file when ready.',
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
                name: 'report',
                in: 'path',
                required: true,
                description: 'Report ID.',
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Report file download.',
                content: new OA\MediaType(
                    mediaType: 'application/octet-stream',
                    schema: new OA\Schema(type: 'string', format: 'binary')
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Report not found.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 409,
                description: 'Report not ready.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
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
