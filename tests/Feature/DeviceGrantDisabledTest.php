<?php

namespace Tests\Feature;

use Tests\TestCase;

class DeviceGrantDisabledTest extends TestCase
{
    public function test_the_device_flow_has_no_routes(): void
    {
        $this->get('/oauth/device')->assertNotFound();
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('passport.device'));
    }
}
