<?php

use App\Business\Pricing\EffectiveRateResolver;
use App\Data\Pricing\RateData;

it('overrides only the matching billing identity', function (): void {
    $rate = fn (int $id, int $interval, string $unit, string $currency, string $source): RateData => new RateData(
        $id,
        $interval,
        $unit,
        '100.00',
        $currency,
        $source,
    );

    $resolved = (new EffectiveRateResolver)->resolve(
        [$rate(10, 1, 'month', 'INR', 'unit')],
        [
            $rate(1, 1, 'month', 'INR', 'unit_type'),
            $rate(2, 3, 'month', 'INR', 'unit_type'),
            $rate(3, 1, 'week', 'INR', 'unit_type'),
        ],
    );

    expect($resolved)->toHaveCount(3)
        ->and(collect($resolved)->first(fn (RateData $item): bool => $item->source === 'unit'))->toMatchObject([
            'id' => 10,
            'billingInterval' => 1,
            'billingUnit' => 'month',
            'currency' => 'INR',
        ])
        ->and(collect($resolved)->where('source', 'unit_type'))->toHaveCount(2);
});

it('keeps the type rate when no active unit override is supplied', function (): void {
    $rate = new RateData(1, 1, 'month', '100.00', 'INR', 'unit_type');

    $resolved = (new EffectiveRateResolver)->resolve([], [$rate]);

    expect($resolved)->toHaveCount(1)
        ->and($resolved[0])->toBe($rate);
});
