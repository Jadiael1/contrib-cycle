<?php

namespace App\Jobs;

use App\Exports\CollectiveProjectPaymentStatusExport;
use App\Models\CollectiveProject;
use App\Models\CollectiveProjectReport;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GenerateCollectiveProjectPaymentStatusReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $reportId,
    ) {}

    public function handle(): void
    {
        $report = CollectiveProjectReport::query()->findOrFail($this->reportId);

        /** @var CollectiveProject $project */
        $project = CollectiveProject::query()->findOrFail($report->collective_project_id);

        $filters = $report->filters ?? [];
        $year = (int) ($filters['year'] ?? now()->year);
        $month = isset($filters['month']) ? (int) $filters['month'] : null;
        $week = isset($filters['week_of_month']) ? (int) $filters['week_of_month'] : null;
        $scope = (string) ($filters['status_scope'] ?? 'accepted_only');

        $disk = (string) ($filters['disk'] ?? 'local'); // recomendo: 'local' + visibility private
        $ext = 'xlsx';

        $fileName = $this->buildFileName($project->slug, $project->payment_interval, $year, $month, $week, $ext);
        $path = "reports/{$project->id}/{$report->id}/{$fileName}";

        $export = new CollectiveProjectPaymentStatusExport(
            project: $project,
            year: $year,
            month: $month,
            weekOfMonth: $week,
            statusScope: $scope
        );

        try {
            // store() é o padrão da lib para salvar em disco :contentReference[oaicite:4]{index=4}
            $export->store($path, $disk);

            $size = Storage::disk($disk)->size($path);

            $report->update([
                'status' => 'ready',
                'file_disk' => $disk,
                'file_path' => $path,
                'file_name' => $fileName,
                // 'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                // 'file_size' => $size,
                // 'generated_at' => now(),
                'error_message' => null,
            ]);
        } catch (Exception $e) {
            $report->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function buildFileName(string $slug, string $interval, int $year, ?int $month, ?int $week, string $ext): string
    {
        $parts = ["payment-status", $slug, $interval, (string)$year];

        if (!is_null($month)) $parts[] = sprintf('%02d', $month);
        if (!is_null($week)) $parts[] = "w{$week}";

        $parts[] = now()->format('Ymd_His');

        return implode('_', $parts) . ".{$ext}";
    }
}
