<?php

use Illuminate\Support\Facades\RateLimiter;

it('limits anonymous public reads per address', function () {
    RateLimiter::clear('public');

    foreach (range(1, 60) as $i) {
        $this->getJson('/api/v1/public/settings')->assertOk();
    }

    $this->getJson('/api/v1/public/settings')->assertStatus(429);
    $this->getJson('/api/v1/meta/locales')->assertStatus(429);
});

it('keeps the health check outside the limits, so monitors never lock themselves out', function () {
    foreach (range(1, 70) as $i) {
        $this->getJson('/api/v1/health')->assertOk();
    }
});

it('limits signed-in calls per account, not per address', function () {
    actingAsRole('reader', ['dashboard.view']);

    foreach (range(1, 300) as $i) {
        $this->getJson('/api/v1/me')->assertOk();
    }

    $this->getJson('/api/v1/me')->assertStatus(429)->assertJsonPath('error.code', 'too_many_requests');

    // Another account from the same address is unaffected.
    actingAsRole('other', ['dashboard.view']);
    $this->getJson('/api/v1/me')->assertOk();
});
