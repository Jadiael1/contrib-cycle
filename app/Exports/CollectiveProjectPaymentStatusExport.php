<?php

namespace App\Exports;

use App\Models\CollectiveProject;
use App\Models\CollectiveProjectPayment;
use App\Models\ProjectMembership;
use Carbon\Carbon;
use Generator;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class CollectiveProjectPaymentStatusExport extends StringValueBinder implements FromGenerator, WithHeadings, WithColumnFormatting, WithCustomValueBinder, ShouldAutoSize
{
    use Exportable;

    public function __construct(
        private readonly CollectiveProject $project,
        private readonly int $year,
        private readonly ?int $month,
        private readonly ?int $weekOfMonth,
        private readonly string $statusScope = 'accepted_only',
    ) {}

    public function headings(): array
    {
        return [
            'Nome',
            'Sobrenome',
            'Telefone',
            'Periodo',
            'Intervalo por periodo',
            'Situacao pagamento',
            'Comprovante',
            'Pago em',
            'Valor esperado',
        ];
    }

    public function generator(): Generator
    {
        $interval = $this->project->payment_interval;
        $per = (int) $this->project->payments_per_interval;

        $slots = $this->buildSlots($interval, $per, $this->year, $this->month, $this->weekOfMonth);

        $membersQuery = ProjectMembership::query()
            ->join('users', 'users.id', '=', 'project_memberships.user_id')
            ->where('project_memberships.collective_project_id', $this->project->id)
            ->select([
                'project_memberships.id as membership_id',
                'project_memberships.user_id',
                'project_memberships.status as membership_status',
                'project_memberships.accepted_at',
                'project_memberships.removed_at',
                'users.first_name',
                'users.last_name',
                'users.phone',
            ])
            ->orderBy('project_memberships.id');

        if ($this->statusScope === 'accepted_only') {
            $membersQuery->where('project_memberships.status', 'accepted');
        } else {
            $membersQuery->whereIn('project_memberships.status', ['accepted', 'removed']);
        }

        $chunkSize = 1000;
        $lastId = 0;

        while (true) {
            $chunk = (clone $membersQuery)
                ->where('project_memberships.id', '>', $lastId)
                ->limit($chunkSize)
                ->get();

            if ($chunk->isEmpty()) {
                break;
            }

            $lastId = (int) $chunk->last()->membership_id;

            $userIds = $chunk->pluck('user_id')->all();

            $payments = $this->loadPaymentsForUsers($interval, $userIds);

            // set rápido: paid[userId][slotKey]=paidAtIso
            $paid = [];
            foreach ($payments as $p) {
                $key = $this->slotKey(
                    (int) $p->period_year,
                    (int) $p->period_month,
                    (int) $p->period_week_of_month,
                    (int) $p->sequence_in_period
                );

                // se houver duplicados (não deveria por unique), fica o mais recente
                $paid[(int) $p->user_id][$key] = [
                    'paid_at' => $p->paid_at?->toISOString(),
                    'receipt_url' => $this->buildReceiptUrl($p->receipt_path),
                ];
            }

            foreach ($chunk as $m) {
                foreach ($slots as $slot) {
                    $payment = $paid[(int) $m->user_id][$slot['key']] ?? null;
                    $paidAtIso = $payment['paid_at'] ?? null;

                    $status = $paidAtIso ? 'paid' : 'pending';

                    yield [
                        $m->first_name,
                        $m->last_name,
                        $m->phone,
                        $slot['label'],
                        $slot['sequence'],
                        $this->translateStatus($status),
                        $this->buildReceiptLink($payment['receipt_url'] ?? null),
                        $paidAtIso,
                        (string) $this->project->amount_per_participant,
                    ];
                }
            }
        }
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function bindValue(Cell $cell, $value): bool
    {
        if ($cell->getColumn() === 'C') {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);
            return true;
        }

        if (is_string($value) && str_starts_with($value, '=HYPERLINK(')) {
            $cell->setValueExplicit($value, DataType::TYPE_FORMULA);
            return true;
        }

        return parent::bindValue($cell, $value);
    }

    private function loadPaymentsForUsers(string $interval, array $userIds)
    {
        $q = CollectiveProjectPayment::query()
            ->where('collective_project_id', $this->project->id)
            ->whereIn('user_id', $userIds)
            ->where('period_year', $this->year);

        if ($interval === 'week' || $interval === 'month') {
            $q->where('period_month', (int) $this->month);
        } else {
            $q->where('period_month', 0);
        }

        if ($interval === 'week') {
            if ($this->weekOfMonth) {
                $q->where('period_week_of_month', (int) $this->weekOfMonth);
            }
        } else {
            $q->where('period_week_of_month', 0);
        }

        return $q->get([
            'user_id',
            'period_year',
            'period_month',
            'period_week_of_month',
            'sequence_in_period',
            'paid_at',
            'receipt_path',
        ]);
    }

    private function buildSlots(string $interval, int $per, int $year, ?int $month, ?int $weekOfMonth): array
    {
        $slots = [];

        if ($interval === 'week') {
            $weeks = $this->weeksInMonth($year, (int) $month);
            $rangeWeeks = $weekOfMonth ? [$weekOfMonth] : range(1, $weeks);

            foreach ($rangeWeeks as $w) {
                for ($seq = 1; $seq <= $per; $seq++) {
                    [$start, $end] = $this->weekRangeBySundays($year, (int)$month, (int)$w);

                    $slots[] = [
                        'key' => $this->slotKey($year, (int)$month, (int)$w, $seq),
                        'label' => sprintf('%04d-%02d / Semana %d', $year, (int)$month, (int)$w),
                        'sequence' => $seq,
                        'end' => $end,
                    ];
                }
            }

            return $slots;
        }

        if ($interval === 'month') {
            for ($seq = 1; $seq <= $per; $seq++) {
                $end = Carbon::create($year, (int)$month, 1)->endOfMonth()->endOfDay();
                $slots[] = [
                    'key' => $this->slotKey($year, (int)$month, 0, $seq),
                    'label' => sprintf('%04d-%02d', $year, (int)$month),
                    'sequence' => $seq,
                    'end' => $end,
                ];
            }
            return $slots;
        }

        // year
        for ($seq = 1; $seq <= $per; $seq++) {
            $end = Carbon::create($year, 12, 31)->endOfDay();
            $slots[] = [
                'key' => $this->slotKey($year, 0, 0, $seq),
                'label' => sprintf('%04d', $year),
                'sequence' => $seq,
                'end' => $end,
            ];
        }

        return $slots;
    }

    private function translateStatus(string $status): string
    {
        return match ($status) {
            'paid' => 'Pago',
            'pending' => 'Pendente',
            default => $status,
        };
    }

    private function buildReceiptUrl(?string $receiptPath): ?string
    {
        if (! $receiptPath) {
            return null;
        }
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return $disk->url($receiptPath);
    }

    private function buildReceiptLink(?string $receiptUrl): ?string
    {
        if (! $receiptUrl) {
            return null;
        }

        return sprintf('=HYPERLINK("%s","Ver comprovante")', $receiptUrl);
    }

    private function slotKey(int $year, int $month, int $week, int $seq): string
    {
        return "{$year}|{$month}|{$week}|{$seq}";
    }

    private function weeksInMonth(int $year, int $month): int
    {
        return count($this->sundaysInMonth($year, $month));
    }

    private function weekRangeBySundays(int $year, int $month, int $week): array
    {
        $firstDay = Carbon::create($year, $month, 1)->startOfDay();
        $endOfMonth = $firstDay->copy()->endOfMonth()->endOfDay();
        $sundays = $this->sundaysInMonth($year, $month);

        if (empty($sundays)) {
            return [$firstDay, $endOfMonth];
        }

        $maxWeek = count($sundays);
        $week = max(1, min($week, $maxWeek));

        if ($week === 1) {
            return [$firstDay, $sundays[0]->copy()->endOfDay()];
        }

        $prevSunday = $sundays[$week - 2]->copy();
        $start = $prevSunday->addDay()->startOfDay();

        if ($week === $maxWeek) {
            return [$start, $endOfMonth];
        }

        return [$start, $sundays[$week - 1]->copy()->endOfDay()];
    }

    private function sundaysInMonth(int $year, int $month): array
    {
        $date = Carbon::create($year, $month, 1)->startOfDay();
        $end = $date->copy()->endOfMonth();
        $sundays = [];

        for ($d = $date->copy(); $d->lte($end); $d->addDay()) {
            if ($d->dayOfWeek === Carbon::SUNDAY) {
                $sundays[] = $d->copy()->startOfDay();
            }
        }

        return $sundays;
    }

    private function weekStart(int $year, int $month, int $week): Carbon
    {
        // start = dia 1 do mês + (week-1)*7, ajustado pro começo do dia
        $d = Carbon::create($year, $month, 1)->startOfDay()->addDays(($week - 1) * 7);
        return $d;
    }
}
