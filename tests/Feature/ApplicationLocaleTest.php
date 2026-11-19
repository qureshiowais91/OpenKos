<?php

use App\Actions\Invoices\GenerateInvoicePdf;
use App\Actions\Reminders\ForceSendReminder;
use App\Jobs\GenerateInvoicePdfArtifact;
use App\Models\Lease;
use App\Models\Setting;
use App\Services\Invoices\InvoicePdfArtifact;
use App\Services\Localization\ApplicationLocale;
use App\Services\Payments\MoneyConverter;

test('installation locales are normalized to supported application locales', function () {
    $locale = app(ApplicationLocale::class);

    expect($locale->normalize('en-US'))->toBe('en')
        ->and($locale->normalize('en-IN'))->toBe('en')
        ->and($locale->normalize('fr'))->toBeNull()
        ->and($locale->normalize(['en-IN']))->toBeNull()
        ->and($locale->resolve(['en-IN']))->toBe('en');
});

test('http requests apply the configured locale and share its catalogs', function () {
    Setting::set('locale', 'en-IN');

    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('app.locale', 'en')
            ->where('app.intl_locale', 'en-IN')
            ->where('i18n.locale', 'en')
            ->where('i18n.fallback', [])
        );
});

test('unsupported installation locales fall back to english', function () {
    Setting::set('locale', 'fr');

    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('app.locale', 'en')
            ->where('app.intl_locale', 'en-IN')
            ->where('i18n.locale', 'en')
        );
});

test('manual reminder actions apply the configured locale', function () {
    Setting::set('locale', 'en-IN');
    app()->setLocale('id');

    $result = app(ForceSendReminder::class)->execute(Lease::factory()->create());

    expect($result)->toBe('all_paid')
        ->and(app()->getLocale())->toBe('en');
});

test('direct money formatting uses the configured locale', function () {
    Setting::set('locale', 'en-IN');
    app()->setLocale('id');

    expect((new MoneyConverter)->format('1500000', 'INR'))
        ->toBe(app(MoneyConverter::class)->format('1500000', 'INR'));
});

test('invoice PDF jobs apply the configured locale and restore the worker locale', function () {
    Setting::set('invoice_pdf_enabled', false, 'boolean');
    Setting::set('locale', 'en-IN');
    app()->setLocale('fr');

    (new GenerateInvoicePdfArtifact(0, ''))->handle(
        app(InvoicePdfArtifact::class),
        app(GenerateInvoicePdf::class),
        app(ApplicationLocale::class),
    );

    expect(app()->getLocale())->toBe('fr');
});
