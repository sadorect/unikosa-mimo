<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CheckModuleEnabled treats a missing setting as *enabled*, so the loan
 * migration writes the switch itself. Without this, a deploy that ran
 * migrations but not seeders would put the module live.
 */
class LoanModuleSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_migrating_alone_leaves_the_module_switched_off(): void
    {
        $this->assertSame('false', Setting::get('loans_enabled'));
    }
}
