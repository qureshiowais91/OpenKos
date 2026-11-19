<?php

namespace Database\Seeders;

use App\Actions\Leases\CreateLease;
use App\Actions\Payments\RecordPayment;
use App\Data\Lease\CreateLeaseData;
use App\Data\Payment\RecordPaymentData;
use App\Enums\BillingStrategy;
use App\Enums\LeaseStatus;
use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\UnitRate;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LeaseSeeder extends Seeder
{
    private const NAMESPACE = 'ope-184-demo';

    private array $activeAssignments = [
        [
            'reference' => 'ope-184-demo-lease-001',
            'property' => 'ope-184-demo-rosewood-residency',
            'unit' => 'A1',
            'tenants' => ['Arjun Sharma'],
            'currency' => 'INR',
            'start_months_ago' => 5,
            'rent_due_day' => 1,
            'payment' => null,
        ],
        [
            'reference' => 'ope-184-demo-lease-002',
            'property' => 'ope-184-demo-rosewood-residency',
            'unit' => 'A2',
            'tenants' => ['Priya Patel'],
            'currency' => 'INR',
            'start_months_ago' => 4,
            'rent_due_day' => 28,
            'payment' => null,
        ],
        [
            'reference' => 'ope-184-demo-lease-003',
            'property' => 'ope-184-demo-rosewood-residency',
            'unit' => 'A3',
            'tenants' => ['Rohan Mehta'],
            'currency' => 'INR',
            'start_months_ago' => 3,
            'rent_due_day' => 10,
            'payment' => ['kind' => 'partial', 'amount' => '7500'],
        ],
        [
            'reference' => 'ope-184-demo-lease-004',
            'property' => 'ope-184-demo-white-lotus-apartments',
            'unit' => 'A1',
            'tenants' => ['Ananya Iyer'],
            'currency' => 'INR',
            'start_months_ago' => 2,
            'rent_due_day' => 5,
            'payment' => ['kind' => 'paid'],
        ],
        [
            'reference' => 'ope-184-demo-lease-005',
            'property' => 'ope-184-demo-white-lotus-apartments',
            'unit' => 'B1',
            'tenants' => ['Vikram Singh'],
            'currency' => 'INR',
            'start_months_ago' => 2,
            'rent_due_day' => 15,
            'payment' => ['kind' => 'pending'],
        ],
        [
            'reference' => 'ope-184-demo-lease-006',
            'property' => 'ope-184-demo-greenview-enclave',
            'unit' => 'A1',
            'tenants' => ['Neha Kapoor'],
            'currency' => 'INR',
            'start_months_ago' => 2,
            'rent_due_day' => 12,
            'payment' => ['kind' => 'paid'],
        ],
        [
            'reference' => 'ope-184-demo-lease-007',
            'property' => 'ope-184-demo-greenview-enclave',
            'unit' => 'B2',
            'tenants' => ['Karthik Reddy'],
            'currency' => 'INR',
            'start_months_ago' => 1,
            'rent_due_day' => 20,
            'payment' => ['kind' => 'partial', 'amount' => '9000'],
        ],
        [
            'reference' => 'ope-184-demo-lease-008',
            'property' => 'ope-184-demo-sunny-meadows-homes',
            'unit' => 'A4',
            'tenants' => ['Sneha Joshi'],
            'currency' => 'INR',
            'start_months_ago' => 2,
            'rent_due_day' => 25,
            'end_days_from_now' => 14,
            'payment' => ['kind' => 'paid'],
        ],
    ];

    private array $sharedAssignments = [
        [
            'reference' => 'ope-184-demo-lease-009',
            'property' => 'ope-184-demo-rosewood-residency',
            'unit' => 'B1',
            'tenants' => ['Aditya Gupta', 'Shreya Bose'],
            'currency' => 'INR',
            'start_months_ago' => 1,
            'rent_due_day' => 7,
            'payment' => ['kind' => 'paid'],
        ],
        [
            'reference' => 'ope-184-demo-lease-010',
            'property' => 'ope-184-demo-rosewood-residency',
            'unit' => 'B2',
            'tenants' => ['Manish Tiwari', 'Kavya Pillai', 'Gaurav Chauhan'],
            'currency' => 'INR',
            'start_months_ago' => 1,
            'rent_due_day' => 8,
            'payment' => ['kind' => 'paid'],
        ],
    ];

    private array $historicalAssignments = [
        ['reference' => 'ope-184-demo-history-001', 'property' => 'ope-184-demo-rosewood-residency', 'unit' => 'A3', 'tenant' => 'Pooja Nair', 'months_ago' => 7, 'currency' => 'INR', 'refund_amount' => null],
        ['reference' => 'ope-184-demo-history-002', 'property' => 'ope-184-demo-white-lotus-apartments', 'unit' => 'B3', 'tenant' => 'Amit Kumar', 'months_ago' => 5, 'currency' => 'INR', 'refund_amount' => '15000'],
        ['reference' => 'ope-184-demo-history-003', 'property' => 'ope-184-demo-greenview-enclave', 'unit' => 'C3', 'tenant' => 'Sameer Khan', 'months_ago' => 4, 'currency' => 'INR', 'refund_amount' => null],
        ['reference' => 'ope-184-demo-history-004', 'property' => 'ope-184-demo-sunny-meadows-homes', 'unit' => 'C4', 'tenant' => 'Ritika Das', 'months_ago' => 3, 'currency' => 'INR', 'refund_amount' => '15000'],
        ['reference' => 'ope-184-demo-history-005', 'property' => 'ope-184-demo-rosewood-residency', 'unit' => 'A2', 'tenant' => 'Rahul Verma', 'months_ago' => 9, 'currency' => 'INR', 'refund_amount' => null],
        ['reference' => 'ope-184-demo-history-006', 'property' => 'ope-184-demo-white-lotus-apartments', 'unit' => 'B4', 'tenant' => 'Divya Menon', 'months_ago' => 6, 'currency' => 'INR', 'refund_amount' => '15000'],
        ['reference' => 'ope-184-demo-history-007', 'property' => 'ope-184-demo-greenview-enclave', 'unit' => 'A2', 'tenant' => 'Nikhil Rao', 'months_ago' => 8, 'currency' => 'INR', 'refund_amount' => null],
    ];

    public function run(): void
    {
        $now = now();
        $this->activeAssignments[0]['rent_due_day'] = $now->day;
        $this->activeAssignments[1]['rent_due_day'] = min($now->day + 3, $now->daysInMonth);

        DB::transaction(function () use ($now): void {
            $this->clearOwnedLeases();

            foreach ($this->activeAssignments as $assignment) {
                $this->createActiveLease($assignment, $now);
            }

            foreach ($this->sharedAssignments as $assignment) {
                $this->createActiveLease($assignment, $now);
            }

            foreach ($this->historicalAssignments as $assignment) {
                $this->createHistoricalLease($assignment, $now);
            }

            $this->seedPayments($now);
        });
    }

    private function clearOwnedLeases(): void
    {
        $leaseIds = Lease::withTrashed()
            ->where(fn ($query) => $query
                ->where('reference', 'like', self::NAMESPACE.'-lease-%')
                ->orWhere('reference', 'like', self::NAMESPACE.'-history-%'))
            ->pluck('id');

        if ($leaseIds->isEmpty()) {
            return;
        }

        Invoice::query()->whereIn('lease_id', $leaseIds)->delete();
        DB::table('lease_tenant')->whereIn('lease_id', $leaseIds)->delete();
        DB::table('lease_unit_histories')->whereIn('lease_id', $leaseIds)->delete();
        DB::table('reminder_logs')->whereIn('lease_id', $leaseIds)->delete();
        Lease::withTrashed()->whereKey($leaseIds)->forceDelete();
    }

    /**
     * @param  array{reference: string, property: string, unit: string, tenants: array<int, string>, currency: string, start_months_ago: int, rent_due_day: int, end_days_from_now?: int, payment?: array{kind: string, amount?: string}|null}  $assignment
     */
    private function createActiveLease(array $assignment, CarbonInterface $now): Lease
    {
        $unit = $this->resolveUnit($assignment['property'], $assignment['unit']);
        $tenantIds = array_map(fn (string $name): int => $this->resolveTenantId($name), $assignment['tenants']);
        $rate = $this->resolveRate($unit, $assignment['currency']);
        $startDate = $now->copy()->subMonthsNoOverflow($assignment['start_months_ago'])->startOfMonth();
        $endDate = isset($assignment['end_days_from_now'])
            ? $now->copy()->addDays($assignment['end_days_from_now'])->toDateString()
            : null;

        /** @var Lease $lease */
        $lease = app(CreateLease::class)->execute($unit, new CreateLeaseData(
            tenantIds: $tenantIds,
            startDate: $startDate->toDateString(),
            endDate: $endDate,
            rentAmount: null,
            billingInterval: null,
            billingUnit: null,
            billingStrategy: BillingStrategy::Advance->value,
            unitRateId: $rate->id,
            depositAmount: '15000',
            depositPaidAt: $startDate->toDateString(),
            depositRefundAmount: null,
            depositRefundedAt: null,
            rentDueDay: $assignment['rent_due_day'],
            notes: 'GrihaFlow demonstration lease.',
        ));

        $lease->forceFill(['reference' => $assignment['reference']])->saveQuietly();

        return $lease->refresh();
    }

    /**
     * @param  array{reference: string, property: string, unit: string, tenant: string, months_ago: int, currency: string, refund_amount: string|null}  $assignment
     */
    private function createHistoricalLease(array $assignment, CarbonInterface $now): Lease
    {
        $unit = $this->resolveUnit($assignment['property'], $assignment['unit']);
        $tenantId = $this->resolveTenantId($assignment['tenant']);
        $rate = $this->resolveRate($unit, $assignment['currency']);
        $endDate = $now->copy()->subMonthsNoOverflow($assignment['months_ago'])->startOfMonth()->addDays(10);
        $startDate = $endDate->copy()->subMonthsNoOverflow(6)->startOfMonth();
        $depositAmount = '15000';

        $lease = Lease::create([
            'reference' => $assignment['reference'],
            'primary_tenant_id' => $tenantId,
            'property_id' => $unit->property_id,
            'unit_id' => $unit->id,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'rent_amount' => $rate->amount,
            'currency' => $rate->currency,
            'billing_interval' => $rate->billing_interval,
            'billing_unit' => $rate->billing_unit,
            'billing_strategy' => BillingStrategy::Advance,
            'is_custom_price' => false,
            'unit_rate_id' => $rate->id,
            'deposit_amount' => $depositAmount,
            'deposit_paid_at' => $startDate,
            'deposit_refund_amount' => $assignment['refund_amount'],
            'deposit_refunded_at' => $assignment['refund_amount'] === null ? null : $endDate,
            'rent_due_day' => 5,
            'status' => LeaseStatus::Terminated,
            'termination_date' => $endDate,
            'termination_reason' => 'contract_ended',
            'notes' => 'GrihaFlow demonstration historical lease.',
        ]);

        $lease->tenants()->sync([$tenantId => ['is_primary' => true]]);

        return $lease;
    }

    private function seedPayments(CarbonInterface $now): void
    {
        $owner = User::query()->where('email', 'budi@grihaflow.com')->firstOrFail();
        $assignments = [...$this->activeAssignments, ...$this->sharedAssignments];

        foreach ($assignments as $assignment) {
            $payment = $assignment['payment'] ?? null;

            if ($payment === null) {
                continue;
            }

            $lease = Lease::query()->where('reference', $assignment['reference'])->firstOrFail();
            $invoice = $lease->invoices()
                ->whereDate('period_start', '<=', $now->toDateString())
                ->orderByDesc('period_start')
                ->firstOrFail();
            $amount = $payment['kind'] === 'paid'
                ? (string) $invoice->outstanding
                : ($payment['amount'] ?? (string) $invoice->outstanding);
            $forcePending = $payment['kind'] === 'pending';

            $result = app(RecordPayment::class)->execute($invoice, new RecordPaymentData(
                amount: $amount,
                paymentDate: $now->copy()->subDay()->toDateString(),
                paymentMethod: PaymentMethod::Transfer->value,
                notes: 'GrihaFlow demonstration payment.',
            ), $owner, $forcePending);

            if ($result->failed() || $result->payment === null) {
                throw new RuntimeException('Unable to record demonstration payment.');
            }

            $result->payment->forceFill([
                'reference_number' => self::NAMESPACE.'-payment-'.substr($assignment['reference'], -3),
            ])->saveQuietly();
        }
    }

    private function resolveUnit(string $propertySlug, string $name): Unit
    {
        $property = Property::query()->where('slug', $propertySlug)->first();

        if (! $property) {
            throw new RuntimeException("Missing demonstration property [{$propertySlug}].");
        }

        $unit = $property->units()->where('name', $name)->first();

        if (! $unit) {
            throw new RuntimeException("Missing demonstration unit [{$propertySlug}/{$name}].");
        }

        return $unit;
    }

    private function resolveTenantId(string $name): int
    {
        $tenant = Tenant::query()->where('name', $name)->first();

        if (! $tenant) {
            throw new RuntimeException("Missing demonstration tenant [{$name}].");
        }

        return $tenant->id;
    }

    private function resolveRate(Unit $unit, string $currency): UnitRate
    {
        $rate = $unit->rates()
            ->where('billing_interval', 1)
            ->where('billing_unit', 'month')
            ->where('currency', $currency)
            ->where('is_active', true)
            ->first();

        if (! $rate) {
            throw new RuntimeException("Missing active {$currency} monthly rate for demonstration unit [{$unit->name}].");
        }

        return $rate;
    }
}
