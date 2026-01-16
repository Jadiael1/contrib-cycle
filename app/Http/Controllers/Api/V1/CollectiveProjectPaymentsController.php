<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCollectiveProjectPaymentRequest;
use App\Http\Resources\Api\V1\CollectiveProjectPaymentResource;
use App\Models\CollectiveProject;
use App\Models\CollectiveProjectPayment;
use App\Models\ProjectMembership;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class CollectiveProjectPaymentsController extends Controller
{
    #[OA\Get(
        path: '/api/v1/projects/{project}/payments',
        tags: ['Participant Payments'],
        summary: 'List payments',
        description: 'Lists payments for the authenticated participant in the project.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'project',
                in: 'path',
                required: true,
                description: 'Project slug.',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Payment list.',
                content: new OA\JsonContent(
                    type: 'object',
                    required: ['data'],
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/CollectiveProjectPaymentResource')
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Participation not confirmed.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
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
        $user = $request->user();

        $membership = ProjectMembership::query()
            ->where('collective_project_id', $project->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $membership || $membership->status !== 'accepted') {
            return response()->json(['message' => 'Participation not confirmed.'], 403);
        }

        $payments = CollectiveProjectPayment::query()
            ->where('collective_project_id', $project->id)
            ->where('user_id', $user->id)
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->get();

        return CollectiveProjectPaymentResource::collection($payments);
    }

    // endpoint pra ajudar o front a montar "Primeira Semana, Segunda..."
    #[OA\Get(
        path: '/api/v1/projects/{project}/payment-options',
        tags: ['Participant Payments'],
        summary: 'Get payment options',
        description: 'Returns helper data for building payment period selectors.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'project',
                in: 'path',
                required: true,
                description: 'Project slug.',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'year',
                in: 'query',
                required: false,
                description: 'Target year for week calculation.',
                schema: new OA\Schema(type: 'integer', example: 2025)
            ),
            new OA\Parameter(
                name: 'month',
                in: 'query',
                required: false,
                description: 'Target month for week calculation (1-12).',
                schema: new OA\Schema(type: 'integer', example: 5)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Payment options.',
                content: new OA\JsonContent(ref: '#/components/schemas/PaymentOptionsResponse')
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
    public function options(Request $request, CollectiveProject $project)
    {
        $interval = $project->payment_interval;
        $per = (int) $project->payments_per_interval;

        $year = (int) ($request->query('year', now()->year));
        $month = $request->query('month');

        $resp = [
            'payment_interval' => $interval,
            'payments_per_interval' => $per,
            'sequence_range' => ['min' => 1, 'max' => $per],
        ];

        if ($interval === 'week') {
            if (!is_null($month)) {
                $m = (int) $month;
                $weeks = $this->weeksInMonth($year, $m);

                $labels = [
                    1 => 'Primeira Semana',
                    2 => 'Segunda Semana',
                    3 => 'Terceira Semana',
                    4 => 'Quarta Semana',
                    5 => 'Quinta Semana',
                    6 => 'Sexta Semana',
                ];

                $resp['weeks_in_month'] = $weeks;
                $resp['weeks'] = collect(range(1, $weeks))->map(fn ($w) => [
                    'value' => $w,
                    'label' => $labels[$w] ?? "Semana {$w}",
                ])->values();
            } else {
                $resp['hint'] = 'Provide year and month query params to get weeks list.';
            }
        }

        return response()->json($resp);
    }

    #[OA\Post(
        path: '/api/v1/projects/{project}/payments',
        tags: ['Participant Payments'],
        summary: 'Create payment',
        description: 'Creates a payment for the authenticated participant.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'project',
                in: 'path',
                required: true,
                description: 'Project slug.',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(ref: '#/components/schemas/StoreCollectiveProjectPaymentRequest')
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Payment created.',
                content: new OA\JsonContent(
                    type: 'object',
                    required: ['data'],
                    properties: [
                        new OA\Property(
                            property: 'data',
                            ref: '#/components/schemas/CollectiveProjectPaymentResource'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Participation not confirmed.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 409,
                description: 'Payment already registered.',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
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
    public function store(StoreCollectiveProjectPaymentRequest $request, CollectiveProject $project)
    {
        $user = $request->user();

        $membership = ProjectMembership::query()
            ->where('collective_project_id', $project->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $membership || $membership->status !== 'accepted') {
            return response()->json(['message' => 'Participation not confirmed.'], 403);
        }

        $data = $request->validated();

        $year = (int) $data['year'];
        $sequence = (int) $data['sequence'];

        // sentinelas
        $month = 0;
        $weekOfMonth = 0;

        if ($project->payment_interval === 'week') {
            $month = (int) $data['month'];
            $weekOfMonth = (int) $data['week_of_month'];
        } elseif ($project->payment_interval === 'month') {
            $month = (int) $data['month'];
            $weekOfMonth = 0;
        } else { // year
            $month = 0;
            $weekOfMonth = 0;
        }

        $paidAt = Carbon::parse($data['paid_at']);

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            // "public" -> storage/app/public
            $receiptPath = $request->file('receipt')
                ->store("project-receipts/{$project->id}/{$user->id}", 'public');
        }

        try {
            $payment = DB::transaction(function () use (
                $project, $user, $year, $month, $weekOfMonth, $sequence, $paidAt, $receiptPath
            ) {
                return CollectiveProjectPayment::create([
                    'collective_project_id' => $project->id,
                    'user_id' => $user->id,

                    'period_year' => $year,
                    'period_month' => $month,
                    'period_week_of_month' => $weekOfMonth,
                    'sequence_in_period' => $sequence,

                    'amount' => $project->amount_per_participant, // snapshot
                    'paid_at' => $paidAt,
                    'receipt_path' => $receiptPath,
                ]);
            });
        } catch (QueryException $e) {
            // MySQL/MariaDB duplicate key
            if ($e->getCode() === '23000') {
                return response()->json([
                    'message' => 'Payment already registered for this period/sequence.',
                ], 409);
            }
            throw $e;
        }

        return (new CollectiveProjectPaymentResource($payment))
            ->response()
            ->setStatusCode(201);
    }

    private function weeksInMonth(int $year, int $month): int
    {
        $firstDay = Carbon::create($year, $month, 1);
        $days = (int) $firstDay->daysInMonth;
        $offset = (int) $firstDay->dayOfWeekIso - 1;
        return (int) ceil(($offset + $days) / 7);
    }
}
