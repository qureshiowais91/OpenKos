<?php

use App\Services\Payments\MoneyConverter;

it('converts major units using the regional currency scale', function () {
    $converter = new MoneyConverter;

    expect($converter->toMoney('1500000.00', 'INR')->minorUnits)->toBe(150_000_000)
        ->and($converter->toMoney('15.50', 'inr')->minorUnits)->toBe(1_550);
});

it('rejects unsupported currency precision', function () {
    $converter = new MoneyConverter;

    expect(fn () => $converter->toMoney('15.501', 'INR'))
        ->toThrow(InvalidArgumentException::class);
});

it('renders legacy historical rows with the regional scale fallback', function () {
    $converter = new MoneyConverter;

    // Legacy installs may hold historical rows in other ISO 4217 codes.
    // They are display-only and use the regional minor-unit scale.
    expect($converter->toMoney('15.50', 'INR')->minorUnits)->toBe(1_550)
        ->and($converter->scales())->toBe(['INR' => 2]);
});

it('enforces the decimal storage envelope', function () {
    $converter = new MoneyConverter;

    expect($converter->normalizeAmount('99999999999999999.99', 'INR'))
        ->toBe('99999999999999999.99');

    expect(fn () => $converter->normalizeAmount('100000000000000000.00', 'INR'))
        ->toThrow(InvalidArgumentException::class);
});

it('formats amounts with Indian digit grouping and the rupee symbol', function () {
    $converter = new MoneyConverter;

    expect($converter->format('123456789.05', 'INR', 'en'))
        ->toBe('₹12,34,56,789.05');
});
