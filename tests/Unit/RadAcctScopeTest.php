<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\RadAcct;
use Illuminate\Support\DB;

class RadAcctScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_active_accepts_recent_start_time_without_acctupdatetime()
    {
        // This test assumes the 'radius' connection uses the same sqlite in-memory DB for tests,
        // or is configured accordingly in phpunit.xml. If not, this test will need to be adapted.

        // Create a radacct record with no acctupdatetime but a recent acctstarttime
        $row = RadAcct::create([
            'username' => 'testuser_active',
            'acctstarttime' => now()->subMinutes(10),
            'acctupdatetime' => null,
            'acctstoptime' => null,
            'acctinputoctets' => 1234,
            'acctoutputoctets' => 4321,
        ]);

        $found = RadAcct::forUser('testuser_active')->active()->exists();

        $this->assertTrue($found, 'RadAcct::active() should detect a recent session even without acctupdatetime');
    }
}
