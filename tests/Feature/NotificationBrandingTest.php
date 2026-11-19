<?php

use App\Models\Setting;
use App\Notifications\TenantInvitation;
use App\Notifications\UserInvitation;

it('brands invitation emails with the configured site name', function () {
    Setting::set('site_name', 'GrihaFlow');

    $tenantMail = (new TenantInvitation('https://example.test/accept'))
        ->toMail((object) [])->toArray()['greeting'] ?? '';

    $userMail = (new UserInvitation('https://example.test/accept'))->toMail((object) []);

    expect($tenantMail)->toContain('GrihaFlow')
        ->and($userMail->subject)->toContain('GrihaFlow')
        ->and($userMail->greeting)->toContain('GrihaFlow')
        ->and($userMail->introLines[0])->toContain('GrihaFlow')
        ->and($userMail->subject)->not->toContain('OpenKos')
        ->and($userMail->greeting)->not->toContain('OpenKos')
        ->and($tenantMail)->not->toContain('OpenKos');
});

it('falls back to the app name when no site name is configured', function () {
    Setting::set('site_name', '');

    $mail = (new TenantInvitation('https://example.test/accept'))
        ->toMail((object) [])->toArray()['greeting'] ?? '';

    expect($mail)->not->toBeEmpty();
});
