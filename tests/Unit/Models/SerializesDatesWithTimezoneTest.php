<?php

use App\Models\Invoice;
use App\Models\User;
use App\Support\DateTimeFormatter;
use Carbon\CarbonImmutable;
use Tests\TestCase;

uses(TestCase::class);

it('serializes model datetimes in the configured display timezone', function () {
    config(['app.display_timezone' => 'Asia/Kolkata']);

    $user = User::make()->forceFill(['created_at' => '2026-08-20 23:30:00']);

    expect($user->toArray()['created_at'])
        ->toBe('2026-08-21T05:00:00.000000+05:30');
});

it('keeps calendar-only casts unchanged', function () {
    config(['app.display_timezone' => 'Asia/Kolkata']);

    $invoice = Invoice::make(['period_start' => '2026-08-20']);

    expect($invoice->toArray()['period_start'])->toBe('2026-08-20');
});

it('falls back to the India-only regional timezone when the display timezone is invalid', function () {
    config(['app.display_timezone' => 'invalid/timezone']);

    $user = User::make()->forceFill(['created_at' => '2026-08-20 23:30:00']);

    expect($user->toArray()['created_at'])
        ->toBe('2026-08-21T05:00:00.000000+05:30');
});

it('uses the configured timezone for explicit date formatting', function () {
    config(['app.display_timezone' => 'Asia/Kolkata']);
    $date = CarbonImmutable::parse('2026-08-20T23:30:00Z');

    expect(DateTimeFormatter::iso($date))
        ->toBe('2026-08-21T05:00:00.000000+05:30')
        ->and(DateTimeFormatter::format($date, 'Y-m-d'))
        ->toBe('2026-08-21');
});
