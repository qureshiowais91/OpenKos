<?php

use App\Services\Settings\InstallationCurrencySettings;
use Tests\TestCase;

uses(TestCase::class);

it('pins the supported currency set to the regional configuration', function () {
    expect(app(InstallationCurrencySettings::class)->supported())->toBe(['INR'])
        ->and(app(InstallationCurrencySettings::class)->default())->toBe('INR');
});

it('rejects currencies outside the regional configuration', function () {
    $settings = app(InstallationCurrencySettings::class);

    expect($settings->supports('INR'))->toBeTrue()
        ->and($settings->supports('USX'))->toBeFalse()
        ->and($settings->supports(null))->toBeTrue()
        ->and($settings->supports('ZZZ'))->toBeFalse();
});

it('normalizes currency lists but keeps regional values intact', function () {
    $settings = app(InstallationCurrencySettings::class);

    expect($settings->normalize([' inr ', 'INR'], default: 'INR'))->toBe(['INR']);
});

it('rejects a default currency outside the normalized list', function () {
    expect(fn () => app(InstallationCurrencySettings::class)->normalize(['INR'], default: 'EUR'))
        ->toThrow(InvalidArgumentException::class);
});

it('normalizes empty lists to nothing', function () {
    expect(fn () => app(InstallationCurrencySettings::class)->normalize([]))
        ->toThrow(InvalidArgumentException::class);
});
