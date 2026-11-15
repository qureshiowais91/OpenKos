<?php

use App\Models\Setting;
use Database\Seeders\RoleAndPermissionSeeder;

uses()->beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    // Test the deterministic (non-SSR) render — the SSR server is an external
    // process not running in tests.
    config(['inertia.ssr.enabled' => false]);
});

it('uses the site_name setting as the display name, not APP_NAME', function () {
    config(['app.name' => 'My Boarding']); // simulate the env value
    Setting::set('site_name', 'GrihaFlow');

    $response = $this->get('/login');

    // Shared Inertia `name` prop drives the client-side title suffix.
    $response->assertInertia(fn ($page) => $page->where('name', 'GrihaFlow'));

    // Server-rendered <title> in the root view.
    $response->assertSee('<title>GrihaFlow</title>', false);
});
