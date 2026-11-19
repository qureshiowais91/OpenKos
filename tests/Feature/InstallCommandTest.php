<?php

use App\Models\Setting;
use App\Models\User;

test('install command creates owner account and india settings', function () {
    $this->artisan('app:install')
        ->expectsQuestion('What is the name of this installation?', 'My Property Mgmt')
        ->expectsQuestion('What is the application URL?', 'http://localhost')
        ->expectsQuestion('What is the owner\'s name?', 'Rahul Sharma')
        ->expectsQuestion('What is the owner\'s email address?', 'rahul@grihaflow.com')
        ->expectsQuestion('Choose a password for the owner account', 'password123')
        ->expectsQuestion('Confirm the password', 'password123')
        ->assertSuccessful();

    $user = User::first();

    expect($user)->not->toBeNull();
    expect($user->name)->toBe('Rahul Sharma');
    expect($user->email)->toBe('rahul@grihaflow.com');
    expect($user->hasRole('owner'))->toBeTrue();

    expect(Setting::get('site_name'))->toBe('My Property Mgmt');
    expect(Setting::get('country_code'))->toBe('IN');
    expect(Setting::get('locale'))->toBe('en');
    expect(Setting::get('currency'))->toBe('INR');
    expect(Setting::get('supported_currencies'))->toBe(['INR']);
    expect(Setting::get('timezone'))->toBe('Asia/Kolkata');
});

test('install command fails when users already exist', function () {
    User::factory()->create();

    $this->artisan('app:install')
        ->expectsOutput('Users already exist. Installation has already been completed.')
        ->assertFailed();
});
