<?php

namespace Database\Seeders;

use App\Models\CollectiveProject;
use App\Models\CollectiveProjectPayment;
use App\Models\CollectiveProjectPaymentMethod;
use App\Models\ProjectMembership;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CollectiveProjectSeeder extends Seeder
{
    public function run(): void
    {
        $faker = fake();

        $admin = User::where('role', 'admin')->first();
        $participants = User::where('role', 'participant')->get();

        if (! $admin) {
            $admin = $participants->first();
        }

        if (! $admin || $participants->isEmpty()) {
            return;
        }

        $participantIds = $participants->pluck('id')->all();
        $frequentParticipantIds = $participants->shuffle()->take(12)->pluck('id')->all();

        $titles = [
            'Community Cycle',
            'Neighborhood Fund',
            'Harvest Pool',
            'Builders Collective',
            'Startup Sprint',
            'Family Savings',
            'Local Ventures',
            'Craft Makers',
            'Green Growth',
            'Health Circle',
            'River Renewal',
            'Market Boost',
            'School Support',
            'Artisans Guild',
            'Tech Bridge',
            'Food Share',
            'Sports Crew',
            'Travel Club',
            'Music Makers',
            'Care Circle',
        ];

        $intervals = [
            ['payment_interval' => 'week', 'payments_per_interval' => 1],
            ['payment_interval' => 'week', 'payments_per_interval' => 2],
            ['payment_interval' => 'month', 'payments_per_interval' => 1],
            ['payment_interval' => 'month', 'payments_per_interval' => 2],
            ['payment_interval' => 'month', 'payments_per_interval' => 4],
            ['payment_interval' => 'year', 'payments_per_interval' => 1],
            ['payment_interval' => 'year', 'payments_per_interval' => 2],
        ];

        $scenarios = [
            ['pending' => 0, 'accepted' => 0, 'removed' => 0, 'frequent' => 0],
            ['pending' => 3, 'accepted' => 0, 'removed' => 0, 'frequent' => 0],
            ['pending' => 2, 'accepted' => 3, 'removed' => 0, 'frequent' => 2],
            ['pending' => 1, 'accepted' => 4, 'removed' => 2, 'frequent' => 2],
            ['pending' => 5, 'accepted' => 0, 'removed' => 0, 'frequent' => 1],
            ['pending' => 2, 'accepted' => 6, 'removed' => 0, 'frequent' => 2],
            ['pending' => 0, 'accepted' => 8, 'removed' => 2, 'frequent' => 3],
            ['pending' => 4, 'accepted' => 4, 'removed' => 1, 'frequent' => 2],
            ['pending' => 0, 'accepted' => 12, 'removed' => 0, 'frequent' => 5],
            ['pending' => 6, 'accepted' => 3, 'removed' => 2, 'frequent' => 2],
            ['pending' => 0, 'accepted' => 15, 'removed' => 3, 'frequent' => 4],
            ['pending' => 8, 'accepted' => 8, 'removed' => 1, 'frequent' => 3],
            ['pending' => 0, 'accepted' => 20, 'removed' => 0, 'frequent' => 5],
            ['pending' => 1, 'accepted' => 1, 'removed' => 5, 'frequent' => 2],
            ['pending' => 10, 'accepted' => 2, 'removed' => 0, 'frequent' => 1],
            ['pending' => 2, 'accepted' => 10, 'removed' => 4, 'frequent' => 3],
            ['pending' => 0, 'accepted' => 6, 'removed' => 6, 'frequent' => 2],
            ['pending' => 3, 'accepted' => 14, 'removed' => 0, 'frequent' => 4],
            ['pending' => 5, 'accepted' => 12, 'removed' => 2, 'frequent' => 4],
            ['pending' => 0, 'accepted' => 25, 'removed' => 0, 'frequent' => 6],
        ];

        $amountOptions = [25.00, 40.00, 60.00, 75.00, 90.00, 120.00, 150.00, 200.00, 250.00, 300.00];

        foreach ($titles as $index => $title) {
            $scenario = $scenarios[$index % count($scenarios)];
            $interval = $intervals[$index % count($intervals)];
            $totalMembers = $scenario['pending'] + $scenario['accepted'] + $scenario['removed'];

            $project = CollectiveProject::create([
                'title' => $title,
                'slug' => Str::slug($title) . '-' . ($index + 1),
                'description' => $faker->boolean(70) ? $faker->sentence(12) : null,
                'participant_limit' => $this->resolveParticipantLimit($totalMembers, $index),
                'amount_per_participant' => $amountOptions[$index % count($amountOptions)],
                'payment_interval' => $interval['payment_interval'],
                'payments_per_interval' => $interval['payments_per_interval'],
                'is_active' => $index % 7 !== 0,
                'created_by_user_id' => $admin->id,
            ]);

            $this->seedPaymentMethods($project, $faker);
            $this->seedMemberships(
                $project,
                $scenario,
                $participantIds,
                $frequentParticipantIds,
                $admin->id,
                $faker
            );
        }
    }

    private function resolveParticipantLimit(int $totalMembers, int $index): int
    {
        $baseline = max($totalMembers, 5);

        switch ($index % 4) {
            case 0:
                $limit = $baseline + random_int(0, 2);
                break;
            case 1:
                $limit = $baseline + random_int(5, 15);
                break;
            case 2:
                $limit = $baseline + random_int(20, 60);
                break;
            default:
                $limit = random_int($baseline, $baseline + 20);
                break;
        }

        return $limit;
    }

    private function seedPaymentMethods(CollectiveProject $project, $faker): void
    {
        $methods = [
            [
                'type' => 'pix',
                'label' => 'Pix Primary',
                'payload' => $this->buildPixPayload($faker),
            ],
            [
                'type' => 'pix',
                'label' => 'Pix Secondary',
                'payload' => $this->buildPixPayload($faker),
            ],
            [
                'type' => 'bank_transfer',
                'label' => 'Bank Transfer Primary',
                'payload' => $this->buildBankTransferPayload($faker),
            ],
            [
                'type' => 'bank_transfer',
                'label' => 'Bank Transfer Secondary',
                'payload' => $this->buildBankTransferPayload($faker),
            ],
        ];

        foreach ($methods as $index => $method) {
            CollectiveProjectPaymentMethod::create([
                'collective_project_id' => $project->id,
                'payment_method_type' => $method['type'],
                'payment_method_payload' => $method['payload'],
                'label' => $method['label'],
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function seedMemberships(
        CollectiveProject $project,
        array $scenario,
        array $participantIds,
        array $frequentParticipantIds,
        int $adminId,
        $faker
    ): void {
        $total = $scenario['pending'] + $scenario['accepted'] + $scenario['removed'];

        if ($total === 0) {
            return;
        }

        $memberIds = $this->pickParticipantIds(
            $participantIds,
            $frequentParticipantIds,
            $total,
            (int) ($scenario['frequent'] ?? 0)
        );

        $offset = 0;
        $pendingIds = array_slice($memberIds, $offset, $scenario['pending']);
        $offset += $scenario['pending'];
        $acceptedIds = array_slice($memberIds, $offset, $scenario['accepted']);
        $offset += $scenario['accepted'];
        $removedIds = array_slice($memberIds, $offset, $scenario['removed']);

        foreach ($pendingIds as $userId) {
            ProjectMembership::create([
                'collective_project_id' => $project->id,
                'user_id' => $userId,
                'status' => 'pending',
            ]);

            $this->seedPaymentsForMember($project, $userId, null, $faker);
        }

        foreach ($acceptedIds as $userId) {
            $acceptedAt = $faker->dateTimeBetween('-6 months', '-1 day');

            ProjectMembership::create([
                'collective_project_id' => $project->id,
                'user_id' => $userId,
                'status' => 'accepted',
                'accepted_at' => $acceptedAt,
            ]);

            $this->seedPaymentsForMember($project, $userId, null, $faker);
        }

        foreach ($removedIds as $userId) {
            $acceptedAt = $faker->dateTimeBetween('-8 months', '-2 weeks');
            $removedAt = $faker->dateTimeBetween($acceptedAt, '-1 day');

            ProjectMembership::create([
                'collective_project_id' => $project->id,
                'user_id' => $userId,
                'status' => 'removed',
                'accepted_at' => $acceptedAt,
                'removed_at' => $removedAt,
                'removed_by_user_id' => $adminId,
            ]);

            $this->seedPaymentsForMember($project, $userId, $removedAt, $faker);
        }
    }

    private function seedPaymentsForMember(
        CollectiveProject $project,
        int $userId,
        ?\DateTimeInterface $referenceDate,
        $faker
    ): void {
        $paymentCount = $faker->randomElement([2, 4]);
        $interval = $project->payment_interval;
        $perInterval = max(1, (int) $project->payments_per_interval);

        $reference = $referenceDate ? Carbon::instance($referenceDate) : Carbon::now();
        $baseDate = $this->shiftDate($reference, $interval, -($paymentCount - 1));

        for ($i = 0; $i < $paymentCount; $i++) {
            $paidAt = $this->shiftDate($baseDate, $interval, $i)
                ->setTime(
                    $faker->numberBetween(8, 20),
                    $faker->numberBetween(0, 59),
                    $faker->numberBetween(0, 59)
                );

            $period = $this->buildPeriodFromDate($interval, $paidAt);
            $sequence = $this->resolveSequenceInPeriod($perInterval, $i, $faker);

            CollectiveProjectPayment::create([
                'collective_project_id' => $project->id,
                'user_id' => $userId,
                'period_year' => $period['period_year'],
                'period_month' => $period['period_month'],
                'period_week_of_month' => $period['period_week_of_month'],
                'sequence_in_period' => $sequence,
                'amount' => $project->amount_per_participant,
                'paid_at' => $paidAt,
            ]);
        }
    }

    private function shiftDate(Carbon $date, string $interval, int $steps): Carbon
    {
        $shifted = $date->copy();

        if ($steps === 0) {
            return $shifted;
        }

        switch ($interval) {
            case 'week':
                return $steps > 0 ? $shifted->addWeeks($steps) : $shifted->subWeeks(abs($steps));
            case 'month':
                return $steps > 0 ? $shifted->addMonths($steps) : $shifted->subMonths(abs($steps));
            case 'year':
                return $steps > 0 ? $shifted->addYears($steps) : $shifted->subYears(abs($steps));
            default:
                return $shifted;
        }
    }

    private function buildPeriodFromDate(string $interval, Carbon $date): array
    {
        $period = [
            'period_year' => (int) $date->format('Y'),
            'period_month' => 0,
            'period_week_of_month' => 0,
        ];

        if ($interval === 'month' || $interval === 'week') {
            $period['period_month'] = (int) $date->format('n');
        }

        if ($interval === 'week') {
            $period['period_week_of_month'] = (int) ceil($date->day / 7);
        }

        return $period;
    }

    private function resolveSequenceInPeriod(int $perInterval, int $index, $faker): int
    {
        if ($perInterval <= 1) {
            return 1;
        }

        if ($faker->boolean(35)) {
            return $faker->numberBetween(1, $perInterval);
        }

        return ($index % $perInterval) + 1;
    }

    private function pickParticipantIds(
        array $participantIds,
        array $frequentParticipantIds,
        int $total,
        int $frequentCount
    ): array {
        $frequentCount = min($frequentCount, $total, count($frequentParticipantIds));

        $selected = collect($frequentParticipantIds)
            ->shuffle()
            ->take($frequentCount)
            ->values();

        $remaining = $total - $selected->count();

        if ($remaining > 0) {
            $additional = collect($participantIds)
                ->diff($selected)
                ->shuffle()
                ->take($remaining);

            $selected = $selected->merge($additional);
        }

        if ($selected->count() < $total) {
            $more = collect($participantIds)
                ->diff($selected)
                ->shuffle()
                ->take($total - $selected->count());

            $selected = $selected->merge($more);
        }

        return $selected->take($total)->values()->all();
    }

    private function buildPixPayload($faker): array
    {
        return [
            'pix_key' => $faker->uuid(),
            'pix_holder_name' => $faker->name(),
        ];
    }

    private function buildBankTransferPayload($faker): array
    {
        $bankNames = [
            'Nubank',
            'Itau',
            'Bradesco',
            'Santander',
            'Banco do Brasil',
            'Caixa',
            'Inter',
            'C6 Bank',
            'Sicredi',
            'Safra',
        ];

        $accountTypes = ['checking', 'savings'];
        $bankCodes = ['001', '033', '104', '237', '341', '260', '077', '748', '422'];

        return [
            'bank_name' => $faker->randomElement($bankNames),
            'bank_code' => $faker->randomElement($bankCodes),
            'agency' => (string) $faker->numberBetween(1000, 9999),
            'account_number' => (string) $faker->numberBetween(10000, 999999),
            'account_type' => $faker->randomElement($accountTypes),
            'account_holder_name' => $faker->name(),
            'document' => $faker->numerify('###.###.###-##'),
        ];
    }
}
