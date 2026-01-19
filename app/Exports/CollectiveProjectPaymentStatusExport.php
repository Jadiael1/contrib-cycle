<?php

namespace App\Exports;

use App\Models\CollectiveProject;
use App\Models\CollectiveProjectPayment;
use App\Models\ProjectMembership;
use Carbon\Carbon;
use Generator;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class CollectiveProjectPaymentStatusExport extends StringValueBinder implements FromGenerator, WithHeadings, WithColumnFormatting, WithCustomValueBinder
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
            'Parcela',
            'Status',
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
                $paid[(int)$p->user_id][$key] = $p->paid_at?->toISOString();
            }

            foreach ($chunk as $m) {
                $acceptedAt = $m->accepted_at ? Carbon::parse($m->accepted_at) : null;

                foreach ($slots as $slot) {
                    // aplica "not_applicable": aceitou depois do fim do slot
                    if ($acceptedAt && $acceptedAt->greaterThan($slot['end'])) {
                        yield [
                            $m->first_name,
                            $m->last_name,
                            $m->phone,
                            $slot['label'],
                            $slot['sequence'],
                            $this->translateStatus('not_applicable'),
                            null,
                            (string) $this->project->amount_per_participant,
                        ];
                        continue;
                    }

                    $paidAtIso = $paid[(int)$m->user_id][$slot['key']] ?? null;

                    if ($paidAtIso) {
                        $status = 'paid';
                    } else {
                        $status = now()->greaterThan($slot['end']) ? 'overdue' : 'pending';
                    }

                    yield [
                        $m->first_name,
                        $m->last_name,
                        $m->phone,
                        $slot['label'],
                        $slot['sequence'],
                        $this->translateStatus($status),
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
                    $start = $this->weekStart($year, (int)$month, (int)$w);
                    $end = (clone $start)->addDays(6)->endOfDay();

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
            'overdue' => 'Em atraso',
            'pending' => 'Pendente',
            'not_applicable' => 'Nao se aplica',
            default => $status,
        };
    }

    private function slotKey(int $year, int $month, int $week, int $seq): string
    {
        return "{$year}|{$month}|{$week}|{$seq}";
    }

    private function weeksInMonth(int $year, int $month): int
    {
        $firstDay = Carbon::create($year, $month, 1);
        $days = (int) $firstDay->daysInMonth;
        $offset = (int) $firstDay->dayOfWeekIso - 1;
        return (int) ceil(($offset + $days) / 7);
    }

    private function weekStart(int $year, int $month, int $week): Carbon
    {
        // start = dia 1 do mês + (week-1)*7, ajustado pro começo do dia
        $d = Carbon::create($year, $month, 1)->startOfDay()->addDays(($week - 1) * 7);
        return $d;
    }
}
