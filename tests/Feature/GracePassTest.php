<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\RadCheck;
use App\Services\GracePassService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GracePassTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Never write to the production RADIUS server during tests.
        // Point the 'radius' connection at the default test database instead.
        config([
            'database.connections.radius' => config('database.connections.' . config('database.default')),
        ]);
        DB::purge('radius');

        $this->createRadiusTables();

        AppSetting::set('grace_pass_enabled', '0');
        AppSetting::set('grace_pass_duration_minutes', '5');
        AppSetting::set('grace_pass_data_limit_mb', '50');
        AppSetting::set('grace_pass_speed_upload', '1024');
        AppSetting::set('grace_pass_speed_download', '2048');
        AppSetting::set('grace_pass_cooldown_hours', '24');

        $this->flushCooldown('AA:BB:CC:DD:EE:FF');
        $this->flushCooldown('AA:BB:CC:DD:EE:01');
        $this->flushCooldown('AA:BB:CC:DD:EE:02');
    }

    protected function createRadiusTables(): void
    {
        $schema = DB::connection('radius')->getSchemaBuilder();

        if (! $schema->hasTable('radcheck')) {
            $schema->create('radcheck', function ($table) {
                $table->increments('id');
                $table->string('username', 64)->default('');
                $table->string('attribute', 64)->default('');
                $table->string('op', 2)->default('==');
                $table->string('value', 253)->default('');
            });
        }

        if (! $schema->hasTable('radreply')) {
            $schema->create('radreply', function ($table) {
                $table->increments('id');
                $table->string('username', 64)->default('');
                $table->string('attribute', 64)->default('');
                $table->string('op', 2)->default('==');
                $table->string('value', 253)->default('');
            });
        }
    }

    protected function flushCooldown(string $mac): void
    {
        AppSetting::set(
            'grace_pass_last_claim_' . strtoupper(str_replace([':', '-', '.'], '', $mac)),
            ''
        );
    }

    public function test_disabled_blocks_claim(): void
    {
        $this->assertFalse(GracePassService::isEnabled());

        $result = GracePassService::claim('AA:BB:CC:DD:EE:FF', '10.0.0.5', 'router-1');

        $this->assertFalse($result['success']);
        $this->assertSame('Grace Pass is currently disabled.', $result['error']);
    }

    public function test_missing_mac_is_rejected(): void
    {
        AppSetting::set('grace_pass_enabled', '1');

        $result = GracePassService::claim(null);

        $this->assertFalse($result['success']);
    }

    public function test_duration_defaults_to_five_minutes(): void
    {
        $this->assertSame(5, GracePassService::getDurationMinutes());
    }

    public function test_claim_creates_radius_credentials(): void
    {
        AppSetting::set('grace_pass_enabled', '1');

        $result = GracePassService::claim('AA:BB:CC:DD:EE:FF', '10.0.0.5', 'router-1');

        $this->assertTrue($result['success'], $result['error'] ?? '');
        $this->assertSame('grace_aabbccddeeff', $result['username']);
        $this->assertSame(5, $result['duration']);
        $this->assertNotEmpty($result['password']);

        $this->assertDatabaseHas('radcheck', [
            'username'  => 'grace_aabbccddeeff',
            'attribute' => 'Cleartext-Password',
            'value'     => $result['password'],
        ], 'radius');

        $this->assertDatabaseHas('radcheck', [
            'username'  => 'grace_aabbccddeeff',
            'attribute' => 'Expiration',
        ], 'radius');

        $this->assertDatabaseHas('radreply', [
            'username'  => 'grace_aabbccddeeff',
            'attribute' => 'Mikrotik-Rate-Limit',
            'value'     => '1024k/2048k',
        ], 'radius');
    }

    public function test_active_pass_is_reused_not_duplicated(): void
    {
        AppSetting::set('grace_pass_enabled', '1');

        $first  = GracePassService::claim('AA:BB:CC:DD:EE:FF');
        $second = GracePassService::claim('AA:BB:CC:DD:EE:FF');

        $this->assertTrue($first['success']);
        $this->assertTrue($second['success']);
        $this->assertSame($first['username'], $second['username']);
        $this->assertSame($first['password'], $second['password']);
    }

    public function test_cooldown_blocks_second_claim(): void
    {
        AppSetting::set('grace_pass_enabled', '1');

        $first = GracePassService::claim('AA:BB:CC:DD:EE:FF');
        $this->assertTrue($first['success']);

        // Force expiry into the past so we're within the cooldown window.
        RadCheck::where('username', $first['username'])
            ->where('attribute', 'Expiration')
            ->update(['value' => now()->subMinutes(1)->format('d M Y H:i')]);

        $second = GracePassService::claim('AA:BB:CC:DD:EE:FF');

        $this->assertFalse($second['success']);
        $this->assertStringContainsString('already used', $second['error']);
    }

    public function test_different_macs_get_separate_passes(): void
    {
        AppSetting::set('grace_pass_enabled', '1');

        $a = GracePassService::claim('AA:BB:CC:DD:EE:01', '10.0.0.5');
        $b = GracePassService::claim('AA:BB:CC:DD:EE:02', '10.0.0.6');

        $this->assertTrue($a['success']);
        $this->assertTrue($b['success']);
        $this->assertNotSame($a['username'], $b['username']);
    }

    public function test_data_limit_writes_total_limit(): void
    {
        AppSetting::set('grace_pass_enabled', '1');
        AppSetting::set('grace_pass_data_limit_mb', '50');

        $result = GracePassService::claim('AA:BB:CC:DD:EE:01');

        $this->assertTrue($result['success']);

        $this->assertDatabaseHas('radreply', [
            'username'  => 'grace_aabbccddee01',
            'attribute' => 'Mikrotik-Total-Limit',
            'value'     => (string) (50 * 1048576),
        ], 'radius');
    }
}