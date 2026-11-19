<?php

use App\Actions\Invoices\GenerateInvoices;
use App\Actions\Leases\CreateLease;
use App\Actions\Leases\MoveOutLease;
use App\Data\Lease\CreateLeaseData;
use App\Data\Lease\MoveOutLeaseData;
use App\Enums\LeaseStatus;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('snapshots currency through the billing chain at creation', function () {
    Setting::set('currency', 'INR');
    $lease = Lease::factory()->create();
    $invoice = Invoice::factory()->create(['lease_id' => $lease->id]);
    $payment = Payment::factory()->for($invoice)->create(['amount' => '12.50']);

    expect($lease->currency)->toBe('INR')
        ->and($invoice->currency)->toBe('INR')
        ->and($payment->currency)->toBe('INR');
});

it('keeps non-null snapshots immutable', function () {
    $invoice = Invoice::factory()->create(['currency' => 'INR']);

    expect(fn () => $invoice->update(['currency' => 'EUR']))
        ->toThrow(LogicException::class)
        ->and(fn () => $invoice->update(['currency' => 'INR']))->not->toThrow(LogicException::class);
});

it('resolves legacy null currency through current settings without persisting it', function () {
    Setting::set('currency', 'INR');
    $invoice = Invoice::factory()->create();
    DB::table('invoices')->whereKey($invoice->id)->update(['currency' => null]);

    expect($invoice->fresh()->currency)->toBe('INR');

    expect(DB::table('invoices')->whereKey($invoice->id)->value('currency'))->toBeNull();
});

it('resolves legacy null currency using a later configured currency', function () {
    Setting::set('currency', 'INR');
    $invoice = Invoice::factory()->create();
    DB::table('invoices')->whereKey($invoice->id)->update(['currency' => null]);

    expect($invoice->fresh()->currency)->toBe('INR')
        ->and(DB::table('invoices')->whereKey($invoice->id)->value('currency'))->toBeNull();
});

it('inherits the default rate currency when no rate is selected', function () {
    Setting::set('currency', 'INR');
    $unit = Unit::factory()->create();
    $unit->rates()->update([
        'amount' => '12.50',
        'currency' => 'INR',
    ]);
    $tenant = Tenant::factory()->create();

    $lease = app(CreateLease::class)->execute($unit, new CreateLeaseData(
        tenantIds: [$tenant->id],
        startDate: '2026-08-01',
        endDate: null,
        rentAmount: null,
        billingInterval: null,
        billingUnit: null,
        billingStrategy: null,
        unitRateId: null,
        depositAmount: null,
        depositPaidAt: null,
        depositRefundAmount: null,
        depositRefundedAt: null,
        rentDueDay: 1,
        notes: null,
    ));

    expect($lease->currency)->toBe('INR')
        ->and($lease->rent_amount)->toBe('12.500');
});

it('rejects generating invoices from legacy amounts invalid for the resolved currency', function () {
    Setting::set('currency', 'INR');
    $lease = Lease::factory()->create([
        'rent_amount' => '1.505',
        'start_date' => '2026-08-01',
    ]);
    DB::table('leases')->whereKey($lease->id)->update(['currency' => null]);

    expect(fn () => app(GenerateInvoices::class)->execute($lease->fresh()))
        ->toThrow(InvalidArgumentException::class, 'more precision');

    expect($lease->invoices()->count())->toBe(0);
});

it('rejects moving a lease onto a rate with a different currency', function () {
    Setting::set('currency', 'INR');
    $sourceUnit = Unit::factory()->create();
    $targetUnit = Unit::factory()->create();
    $tenant = Tenant::factory()->create();
    $lease = Lease::factory()->create([
        'unit_id' => $sourceUnit->id,
        'primary_tenant_id' => $tenant->id,
        'currency' => 'INR',
        'status' => LeaseStatus::Active,
    ]);
    // Simulate a legacy historical rate stored in a non-INR currency.
    DB::table('unit_rates')
        ->where('unit_id', $targetUnit->id)
        ->where('billing_interval', 1)
        ->where('billing_unit', 'month')
        ->update(['currency' => 'USD']);

    expect(fn () => app(MoveOutLease::class)->execute($lease, new MoveOutLeaseData(
        terminationDate: '2026-08-21',
        endDate: '2026-08-21',
        reason: 'Currency mismatch test',
        moveToAnotherUnit: true,
        targetUnitId: $targetUnit->id,
    )))->toThrow(HttpException::class);

    expect($lease->fresh()->status)->toBe(LeaseStatus::Active)
        ->and(Lease::query()->where('unit_id', $targetUnit->id)->exists())->toBeFalse();
});

it('rejects merging a lease into an occupied unit with a different currency', function () {
    Setting::set('currency', 'INR');
    $sourceUnit = Unit::factory()->create();
    $targetUnit = Unit::factory()->create(['capacity' => 2]);
    $sourceTenant = Tenant::factory()->create();
    $targetTenant = Tenant::factory()->create();
    $sourceLease = Lease::factory()->create([
        'unit_id' => $sourceUnit->id,
        'primary_tenant_id' => $sourceTenant->id,
        'currency' => 'INR',
        'status' => LeaseStatus::Active,
    ]);
    Lease::factory()->create([
        'unit_id' => $targetUnit->id,
        'primary_tenant_id' => $targetTenant->id,
        'currency' => 'INR',
        'status' => LeaseStatus::Active,
    ]);
    // Simulate a legacy historical lease stored in a non-INR currency.
    DB::table('leases')->where('unit_id', $targetUnit->id)->update(['currency' => 'USD']);

    expect(fn () => app(MoveOutLease::class)->execute($sourceLease, new MoveOutLeaseData(
        terminationDate: '2026-08-21',
        endDate: '2026-08-21',
        reason: 'Currency mismatch merge test',
        moveToAnotherUnit: true,
        targetUnitId: $targetUnit->id,
    )))->toThrow(HttpException::class);

    expect($sourceLease->fresh()->status)->toBe(LeaseStatus::Active)
        ->and($targetUnit->leases()->firstOrFail()->tenants)->toHaveCount(1);
});

it('does not use inactive rates when moving a lease', function () {
    Setting::set('currency', 'INR');
    $sourceUnit = Unit::factory()->create();
    $targetUnit = Unit::factory()->create();
    // Simulate a legacy inactive rate stored in a non-INR currency.
    $targetUnit->rates()->create([
        'billing_interval' => 1,
        'billing_unit' => 'month',
        'amount' => '95.00',
        'currency' => 'USD',
        'is_active' => false,
    ]);
    $tenant = Tenant::factory()->create();
    $sourceLease = Lease::factory()->create([
        'unit_id' => $sourceUnit->id,
        'primary_tenant_id' => $tenant->id,
        'currency' => 'INR',
        'status' => LeaseStatus::Active,
    ]);
    // Simulate a legacy lease in a non-INR currency; the active rate on the
    // target unit is INR, so only the legacy inactive rate could match.
    Lease::query()->whereKey($sourceLease->id)->update(['currency' => 'USD']);
    $sourceLease->refresh();

    expect(fn () => app(MoveOutLease::class)->execute($sourceLease, new MoveOutLeaseData(
        terminationDate: '2026-08-21',
        endDate: '2026-08-21',
        reason: 'Inactive rate test',
        moveToAnotherUnit: true,
        targetUnitId: $targetUnit->id,
    )))->toThrow(HttpException::class);

    expect($sourceLease->fresh()->status)->toBe(LeaseStatus::Active)
        ->and(Lease::query()->where('unit_id', $targetUnit->id)->exists())->toBeFalse();
});

it('rejects invalid legacy amounts when moving a lease', function () {
    Setting::set('currency', 'INR');
    $sourceUnit = Unit::factory()->create();
    $targetUnit = Unit::factory()->create();
    $tenant = Tenant::factory()->create();
    $sourceLease = Lease::factory()->create([
        'unit_id' => $sourceUnit->id,
        'primary_tenant_id' => $tenant->id,
        'rent_amount' => '1000000',
        'deposit_amount' => '1.505',
        'start_date' => '2026-08-01',
        'status' => LeaseStatus::Active,
    ]);
    DB::table('leases')->whereKey($sourceLease->id)->update(['currency' => null]);

    expect(fn () => app(MoveOutLease::class)->execute($sourceLease, new MoveOutLeaseData(
        terminationDate: '2026-08-21',
        endDate: '2026-08-21',
        reason: 'Invalid legacy amount test',
        moveToAnotherUnit: true,
        targetUnitId: $targetUnit->id,
    )))->toThrow(HttpException::class);

    expect($sourceLease->fresh()->status)->toBe(LeaseStatus::Active)
        ->and(Lease::query()->where('unit_id', $targetUnit->id)->exists())->toBeFalse();
});
