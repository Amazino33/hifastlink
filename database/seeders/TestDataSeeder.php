<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\Device;
use App\Models\Otp;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Router;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedRoles();
        $this->seedRouters();
        $this->seedPlans();
        $users = $this->seedUsers();
        $this->seedDevices($users);
        $this->seedVouchers($users);
        $this->seedPaymentsAndTransactions($users);
        $this->seedAppSettings();
        $this->seedOtps();

        $this->command->info('Test data seeded successfully.');
    }

    // ── Roles ────────────────────────────────────────────────────

    private function seedRoles(): void
    {
        $roles = ['super_admin', 'admin', 'staff', 'cashier', 'affiliate', 'panel_user'];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $this->command->info('Roles seeded: ' . implode(', ', $roles));
    }

    // ── Routers ──────────────────────────────────────────────────

    private function seedRouters(): void
    {
        $routers = [
            [
                'name'           => 'Main Hub Router',
                'location'       => '12 Marina Road, Lagos Island',
                'ip_address'     => '10.0.0.1',
                'nas_identifier' => 'main_hub',
                'secret'         => 'testing123',
                'api_user'       => 'admin',
                'api_password'   => 'admin',
                'api_port'       => 8728,
                'is_active'      => true,
                'description'    => 'Primary hub serving Lagos Island',
                'last_seen_at'   => now(),
            ],
            [
                'name'           => 'Lekki Branch Router',
                'location'       => '45 Admiralty Way, Lekki Phase 1',
                'ip_address'     => '10.0.0.2',
                'nas_identifier' => 'lekki_branch',
                'secret'         => 'testing456',
                'api_user'       => 'admin',
                'api_password'   => 'admin',
                'api_port'       => 8728,
                'is_active'      => true,
                'description'    => 'Lekki Phase 1 branch',
                'last_seen_at'   => now()->subMinutes(2),
            ],
            [
                'name'           => 'Ikeja Router (Offline)',
                'location'       => '88 Allen Avenue, Ikeja',
                'ip_address'     => '10.0.0.3',
                'nas_identifier' => 'ikeja_router',
                'secret'         => 'testing789',
                'api_user'       => 'admin',
                'api_password'   => 'admin',
                'api_port'       => 8728,
                'is_active'      => true,
                'description'    => 'Ikeja branch — offline for testing',
                'last_seen_at'   => now()->subHours(2),
            ],
            [
                'name'           => 'Disabled Router',
                'location'       => 'Warehouse',
                'ip_address'     => '10.0.0.4',
                'nas_identifier' => 'disabled_router',
                'secret'         => 'testing000',
                'is_active'      => false,
                'description'    => 'Decommissioned router',
            ],
        ];

        foreach ($routers as $r) {
            Router::firstOrCreate(['nas_identifier' => $r['nas_identifier']], $r);
        }

        $this->command->info('Routers seeded: ' . count($routers));
    }

    // ── Plans ────────────────────────────────────────────────────

    private function seedPlans(): void
    {
        $mainRouter = Router::where('nas_identifier', 'main_hub')->first();
        $lekkiRouter = Router::where('nas_identifier', 'lekki_branch')->first();

        $plans = [
            // Daily plans
            [
                'name' => 'Daily Lite', 'price' => 200, 'data_limit' => 500,
                'limit_unit' => 'MB', 'validity_days' => 1, 'max_devices' => 1,
                'speed_limit_upload' => 512, 'speed_limit_download' => 1024,
                'is_active' => true, 'sort_order' => 1,
                'features' => ['24/7 Support', 'Instant Activation'],
            ],
            [
                'name' => 'Daily Standard', 'price' => 400, 'data_limit' => 1,
                'limit_unit' => 'GB', 'validity_days' => 1, 'max_devices' => 2,
                'speed_limit_upload' => 1024, 'speed_limit_download' => 2048,
                'is_active' => true, 'sort_order' => 2, 'is_featured' => true,
                'features' => ['24/7 Support', 'Instant Activation', '2 Devices'],
            ],
            [
                'name' => 'Night Owl (11PM-6AM)', 'price' => 150, 'data_limit' => 3,
                'limit_unit' => 'GB', 'validity_days' => 1, 'max_devices' => 1,
                'speed_limit_upload' => 2048, 'speed_limit_download' => 4096,
                'allowed_login_time' => 'Al2300-0600',
                'is_active' => true, 'sort_order' => 3,
                'features' => ['Night-only', 'Fast Speed', 'Instant Activation'],
            ],
            // Weekly plans
            [
                'name' => 'Weekly Basic', 'price' => 1500, 'data_limit' => 5,
                'limit_unit' => 'GB', 'validity_days' => 7, 'max_devices' => 2,
                'speed_limit_upload' => 1024, 'speed_limit_download' => 2048,
                'is_active' => true, 'sort_order' => 10,
                'features' => ['24/7 Support', '2 Devices'],
            ],
            [
                'name' => 'Weekly Premium', 'price' => 2500, 'data_limit' => 15,
                'limit_unit' => 'GB', 'validity_days' => 7, 'max_devices' => 3,
                'speed_limit_upload' => 2048, 'speed_limit_download' => 4096,
                'is_active' => true, 'sort_order' => 11, 'is_featured' => true,
                'features' => ['24/7 Support', '3 Devices', 'Priority Speed'],
            ],
            [
                'name' => 'Weekend Binge', 'price' => 800, 'data_limit' => 10,
                'limit_unit' => 'GB', 'validity_days' => 2, 'max_devices' => 2,
                'allowed_login_time' => 'Sa0000-2400,Su0000-2400',
                'is_active' => true, 'sort_order' => 12,
                'features' => ['Weekend Only', '10GB'],
            ],
            // Monthly plans
            [
                'name' => 'Monthly Starter', 'price' => 5000, 'data_limit' => 30,
                'limit_unit' => 'GB', 'validity_days' => 30, 'max_devices' => 3,
                'speed_limit_upload' => 2048, 'speed_limit_download' => 4096,
                'is_active' => true, 'sort_order' => 20,
                'features' => ['24/7 Support', '3 Devices', 'Rollover Data'],
            ],
            [
                'name' => 'Monthly Unlimited', 'price' => 12000, 'data_limit' => null,
                'limit_unit' => 'Unlimited', 'validity_days' => 30, 'max_devices' => 5,
                'speed_limit_upload' => 4096, 'speed_limit_download' => 8192,
                'is_active' => true, 'sort_order' => 21, 'is_featured' => true,
                'features' => ['Unlimited Data', '5 Devices', 'Priority Support', 'Rollover'],
            ],
            // Family plans
            [
                'name' => 'Family Basic', 'price' => 8000, 'data_limit' => 50,
                'limit_unit' => 'GB', 'validity_days' => 30, 'max_devices' => 5,
                'speed_limit_upload' => 2048, 'speed_limit_download' => 4096,
                'is_active' => true, 'is_family' => true, 'family_limit' => 4,
                'sort_order' => 30,
                'features' => ['4 Family Members', '5 Devices', 'Shared Data'],
            ],
            [
                'name' => 'Family Unlimited', 'price' => 20000, 'data_limit' => null,
                'limit_unit' => 'Unlimited', 'validity_days' => 30, 'max_devices' => 10,
                'speed_limit_upload' => 4096, 'speed_limit_download' => 8192,
                'is_active' => true, 'is_family' => true, 'family_limit' => 6,
                'sort_order' => 31, 'is_featured' => true,
                'features' => ['6 Family Members', '10 Devices', 'Unlimited Data'],
            ],
            // Router-specific plan
            [
                'name' => 'Lekki Special', 'price' => 3000, 'data_limit' => 20,
                'limit_unit' => 'GB', 'validity_days' => 14, 'max_devices' => 2,
                'speed_limit_upload' => 2048, 'speed_limit_download' => 4096,
                'is_active' => true, 'sort_order' => 40,
                'router_id' => $lekkiRouter?->id,
                'features' => ['Lekki Branch Only', '2 Devices'],
            ],
            // Admin-only plan
            [
                'name' => 'Staff Plan (Hidden)', 'price' => 0, 'data_limit' => null,
                'limit_unit' => 'Unlimited', 'validity_days' => 90, 'max_devices' => 3,
                'is_active' => true, 'is_admin_only' => true, 'sort_order' => 99,
                'features' => ['Staff Only', 'Unlimited', '90 Days'],
            ],
            // Inactive plan
            [
                'name' => 'Discontinued Plan', 'price' => 999, 'data_limit' => 2,
                'limit_unit' => 'GB', 'validity_days' => 7, 'max_devices' => 1,
                'is_active' => false, 'sort_order' => 100,
            ],
        ];

        foreach ($plans as $p) {
            Plan::firstOrCreate(['name' => $p['name']], $p);
        }

        $this->command->info('Plans seeded: ' . count($plans));
    }

    // ── Users ────────────────────────────────────────────────────

    private function seedUsers(): array
    {
        $mainRouter = Router::where('nas_identifier', 'main_hub')->first();
        $monthlyPlan = Plan::where('name', 'Monthly Starter')->first();
        $unlimitedPlan = Plan::where('name', 'Monthly Unlimited')->first();
        $familyPlan = Plan::where('name', 'Family Basic')->first();
        $dailyPlan = Plan::where('name', 'Daily Standard')->first();
        $weeklyPlan = Plan::where('name', 'Weekly Basic')->first();

        $users = [];

        // 1. Admin (you)
        $admin = User::firstOrCreate(['email' => 'amazino33@gmail.com'], [
            'name'              => 'Osmund Peter',
            'username'          => 'osmund',
            'email'             => 'amazino33@gmail.com',
            'phone'             => '+2348012345678',
            'password'          => Hash::make('password'),
            'radius_password'   => 'admin123',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'plan_id'           => $unlimitedPlan?->id,
            'data_used'         => 0,
            'data_limit'        => null,
            'plan_expiry'       => now()->addDays(90),
            'plan_started_at'   => now()->subDays(5),
            'connection_status' => 'active',
            'router_id'         => $mainRouter?->id,
        ]);
        $admin->assignRole('super_admin');
        $users['admin'] = $admin;

        // 2. Staff user
        $staff = User::firstOrCreate(['username' => 'staffmember'], [
            'name'              => 'Tunde Bakare',
            'username'          => 'staffmember',
            'email'             => 'tunde@hifastlink.ng',
            'phone'             => '+2348023456789',
            'password'          => Hash::make('password'),
            'radius_password'   => 'staff123',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'connection_status' => 'active',
        ]);
        $staff->assignRole('staff');
        $users['staff'] = $staff;

        // 3. Active subscriber — monthly plan, data partially used
        $activeUser = User::firstOrCreate(['username' => 'chioma_active'], [
            'name'              => 'Chioma Okafor',
            'username'          => 'chioma_active',
            'email'             => 'chioma@example.com',
            'phone'             => '+2348034567890',
            'password'          => Hash::make('password'),
            'radius_password'   => 'chioma123',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'plan_id'           => $monthlyPlan?->id,
            'data_used'         => 10737418240, // 10GB used
            'data_limit'        => 32212254720, // 30GB
            'plan_expiry'       => now()->addDays(18),
            'plan_started_at'   => now()->subDays(12),
            'connection_status' => 'active',
            'router_id'         => $mainRouter?->id,
        ]);
        $users['active'] = $activeUser;

        // 4. Expired plan user
        $expiredUser = User::firstOrCreate(['username' => 'emeka_expired'], [
            'name'              => 'Emeka Nwosu',
            'username'          => 'emeka_expired',
            'email'             => 'emeka@example.com',
            'phone'             => '+2348045678901',
            'password'          => Hash::make('password'),
            'radius_password'   => 'emeka123',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'plan_id'           => $dailyPlan?->id,
            'data_used'         => 536870912, // 500MB
            'data_limit'        => 1073741824, // 1GB
            'plan_expiry'       => now()->subDays(3),
            'plan_started_at'   => now()->subDays(4),
            'connection_status' => 'inactive',
        ]);
        $users['expired'] = $expiredUser;

        // 5. Data exhausted user (plan still valid but 0 data left)
        $exhaustedUser = User::firstOrCreate(['username' => 'ada_nodata'], [
            'name'              => 'Ada Eze',
            'username'          => 'ada_nodata',
            'email'             => 'ada@example.com',
            'phone'             => '+2348056789012',
            'password'          => Hash::make('password'),
            'radius_password'   => 'ada123',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'plan_id'           => $weeklyPlan?->id,
            'data_used'         => 5368709120, // 5GB — fully used
            'data_limit'        => 5368709120, // 5GB
            'plan_expiry'       => now()->addDays(4),
            'plan_started_at'   => now()->subDays(3),
            'connection_status' => 'inactive',
        ]);
        $users['exhausted'] = $exhaustedUser;

        // 6. Family admin with children
        $familyHead = User::firstOrCreate(['username' => 'bola_family'], [
            'name'              => 'Bola Adeyemi',
            'username'          => 'bola_family',
            'email'             => 'bola@example.com',
            'phone'             => '+2348067890123',
            'password'          => Hash::make('password'),
            'radius_password'   => 'bola123',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'plan_id'           => $familyPlan?->id,
            'data_used'         => 8589934592, // 8GB
            'data_limit'        => 53687091200, // 50GB
            'plan_expiry'       => now()->addDays(22),
            'plan_started_at'   => now()->subDays(8),
            'is_family_admin'   => true,
            'family_limit'      => 4,
            'connection_status' => 'active',
            'router_id'         => $mainRouter?->id,
        ]);
        $users['family_head'] = $familyHead;

        // Family children
        $child1 = User::firstOrCreate(['username' => 'bola_child1'], [
            'name'              => 'Kemi Adeyemi',
            'username'          => 'bola_child1',
            'phone'             => '+2348078901234',
            'radius_password'   => 'kemi123',
            'phone_verified_at' => now(),
            'parent_id'         => $familyHead->id,
            'connection_status' => 'active',
        ]);
        $users['child1'] = $child1;

        $child2 = User::firstOrCreate(['username' => 'bola_child2'], [
            'name'              => 'Femi Adeyemi',
            'username'          => 'bola_child2',
            'phone'             => '+2348089012345',
            'radius_password'   => 'femi123',
            'phone_verified_at' => now(),
            'parent_id'         => $familyHead->id,
            'connection_status' => 'inactive',
        ]);
        $users['child2'] = $child2;

        // 7. Phone-only user (new captive portal flow — no email, no password, no name)
        $phoneOnly = User::firstOrCreate(['phone' => '+2349011223344'], [
            'name'              => null,
            'username'          => 'user_9011223344',
            'email'             => null,
            'phone'             => '+2349011223344',
            'password'          => null,
            'radius_password'   => Str::random(12),
            'phone_verified_at' => now(),
            'connection_status' => 'active',
        ]);
        $users['phone_only'] = $phoneOnly;

        // 8. Phone-only user WITH a plan (simulates completed captive portal + payment)
        $phoneWithPlan = User::firstOrCreate(['phone' => '+2349022334455'], [
            'name'              => null,
            'username'          => 'user_9022334455',
            'email'             => null,
            'phone'             => '+2349022334455',
            'password'          => null,
            'radius_password'   => Str::random(12),
            'phone_verified_at' => now(),
            'plan_id'           => $dailyPlan?->id,
            'data_used'         => 214748364, // ~200MB
            'data_limit'        => 1073741824, // 1GB
            'plan_expiry'       => now()->addHours(18),
            'plan_started_at'   => now()->subHours(6),
            'connection_status' => 'active',
            'router_id'         => $mainRouter?->id,
        ]);
        $users['phone_with_plan'] = $phoneWithPlan;

        // 9. Google OAuth user (no password, has google_id)
        $googleUser = User::firstOrCreate(['google_id' => 'google_123456789'], [
            'name'              => 'Ngozi Eze',
            'username'          => 'ngozi_google',
            'email'             => 'ngozi.eze@gmail.com',
            'google_id'         => 'google_123456789',
            'phone'             => '+2348090123456',
            'password'          => null,
            'radius_password'   => Str::random(12),
            'email_verified_at' => now(),
            'connection_status' => 'active',
        ]);
        $users['google'] = $googleUser;

        // 10. Unverified user (should be blocked by middleware)
        $unverified = User::firstOrCreate(['username' => 'unverified_user'], [
            'name'              => 'Unverified Person',
            'username'          => 'unverified_user',
            'email'             => 'unverified@example.com',
            'phone'             => '+2348001112222',
            'password'          => Hash::make('password'),
            'radius_password'   => 'unverified123',
            'connection_status' => 'inactive',
        ]);
        $users['unverified'] = $unverified;

        // 11. User with rollover data
        $rolloverUser = User::firstOrCreate(['username' => 'yusuf_rollover'], [
            'name'              => 'Yusuf Ibrahim',
            'username'          => 'yusuf_rollover',
            'email'             => 'yusuf@example.com',
            'phone'             => '+2348002223333',
            'password'          => Hash::make('password'),
            'radius_password'   => 'yusuf123',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'plan_id'           => $monthlyPlan?->id,
            'data_used'         => 5368709120, // 5GB
            'data_limit'        => 32212254720, // 30GB
            'plan_expiry'       => now()->addDays(5),
            'plan_started_at'   => now()->subDays(25),
            'rollover_available_bytes' => 5368709120, // 5GB rollover
            'rollover_validity_days'   => 30,
            'connection_status' => 'active',
        ]);
        $users['rollover'] = $rolloverUser;

        $this->command->info('Users seeded: ' . count($users));

        return $users;
    }

    // ── Devices ──────────────────────────────────────────────────

    private function seedDevices(array $users): void
    {
        $mainRouter = Router::where('nas_identifier', 'main_hub')->first();
        $lekkiRouter = Router::where('nas_identifier', 'lekki_branch')->first();

        $devices = [
            // Admin's devices
            ['user_id' => $users['admin']->id, 'mac' => 'AA:BB:CC:11:22:33', 'router_id' => $mainRouter?->id, 'ip' => '10.0.0.101', 'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0)', 'is_connected' => true],
            ['user_id' => $users['admin']->id, 'mac' => 'AA:BB:CC:11:22:44', 'router_id' => $mainRouter?->id, 'ip' => '10.0.0.102', 'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X)', 'is_connected' => false],

            // Active user's device
            ['user_id' => $users['active']->id, 'mac' => 'DD:EE:FF:33:44:55', 'router_id' => $mainRouter?->id, 'ip' => '10.0.0.103', 'user_agent' => 'Mozilla/5.0 (Linux; Android 14)', 'is_connected' => true],

            // Phone-only user with plan — connected via captive portal
            ['user_id' => $users['phone_with_plan']->id, 'mac' => '11:22:33:44:55:66', 'router_id' => $mainRouter?->id, 'ip' => '10.0.0.110', 'user_agent' => 'Mozilla/5.0 (Linux; Android 13; Samsung)', 'is_connected' => true],

            // Family head + child
            ['user_id' => $users['family_head']->id, 'mac' => 'FA:MI:LY:00:00:01', 'router_id' => $mainRouter?->id, 'ip' => '10.0.0.120', 'is_connected' => true],
            ['user_id' => $users['child1']->id, 'mac' => 'FA:MI:LY:00:00:02', 'router_id' => $mainRouter?->id, 'ip' => '10.0.0.121', 'is_connected' => true],

            // Disconnected device (expired user)
            ['user_id' => $users['expired']->id, 'mac' => 'EX:PI:RE:DD:00:01', 'router_id' => $mainRouter?->id, 'ip' => '10.0.0.130', 'is_connected' => false],

            // Voucher device (no user_id)
            ['user_id' => null, 'mac' => 'VC:HR:DE:VI:CE:01', 'router_id' => $mainRouter?->id, 'ip' => '10.0.0.200', 'is_connected' => true, 'meta' => ['voucher_code' => 'VCH-TESTCODE']],
        ];

        foreach ($devices as $d) {
            Device::firstOrCreate(
                ['mac' => $d['mac']],
                array_merge($d, [
                    'first_seen' => now()->subDays(rand(1, 30)),
                    'last_seen'  => $d['is_connected'] ? now() : now()->subHours(rand(1, 48)),
                ])
            );
        }

        $this->command->info('Devices seeded: ' . count($devices));
    }

    // ── Vouchers ─────────────────────────────────────────────────

    private function seedVouchers(array $users): void
    {
        $mainRouter = Router::where('nas_identifier', 'main_hub')->first();

        $vouchers = [
            // Active voucher from family head — unused
            [
                'code'         => 'VCH-TESTCODE',
                'created_by'   => $users['family_head']->id,
                'router_id'    => $mainRouter?->id,
                'max_uses'     => 3,
                'used_count'   => 1,
                'duration_hours' => 24,
                'is_unlimited' => false,
                'data_limit_mb' => 1024,
                'label'        => 'For guest room',
            ],
            // Fully used voucher
            [
                'code'         => 'VCH-FULLUSED',
                'created_by'   => $users['family_head']->id,
                'max_uses'     => 1,
                'used_count'   => 1,
                'duration_hours' => 12,
                'is_unlimited' => false,
                'data_limit_mb' => 500,
                'expires_at'   => now()->addHours(6),
                'used_at'      => now()->subHours(6),
                'label'        => 'One-time guest',
            ],
            // Expired voucher
            [
                'code'         => 'VCH-EXPIRED1',
                'created_by'   => $users['family_head']->id,
                'max_uses'     => 5,
                'used_count'   => 2,
                'duration_hours' => 4,
                'is_unlimited' => false,
                'data_limit_mb' => 2048,
                'expires_at'   => now()->subDays(2),
                'used_at'      => now()->subDays(2),
                'label'        => 'Expired party voucher',
            ],
            // Unlimited voucher (still active)
            [
                'code'         => 'VCH-UNLIMIT1',
                'created_by'   => $users['admin']->id,
                'max_uses'     => 10,
                'used_count'   => 3,
                'duration_hours' => 48,
                'is_unlimited' => true,
                'label'        => 'VIP unlimited',
                'speed_limit_upload'   => 4096,
                'speed_limit_download' => 8192,
            ],
            // Voucher with speed limits
            [
                'code'         => 'VCH-SPEEDLM1',
                'created_by'   => $users['active']->id,
                'max_uses'     => 2,
                'used_count'   => 0,
                'duration_hours' => 6,
                'is_unlimited' => false,
                'data_limit_mb' => 256,
                'speed_limit_upload'   => 256,
                'speed_limit_download' => 512,
                'label'        => 'Slow guest wifi',
            ],
        ];

        foreach ($vouchers as $v) {
            Voucher::firstOrCreate(['code' => $v['code']], $v);
        }

        $this->command->info('Vouchers seeded: ' . count($vouchers));
    }

    // ── Payments & Transactions ──────────────────────────────────

    private function seedPaymentsAndTransactions(array $users): void
    {
        $mainRouter = Router::where('nas_identifier', 'main_hub')->first();
        $monthlyPlan = Plan::where('name', 'Monthly Starter')->first();
        $dailyPlan = Plan::where('name', 'Daily Standard')->first();
        $familyPlan = Plan::where('name', 'Family Basic')->first();
        $unlimitedPlan = Plan::where('name', 'Monthly Unlimited')->first();

        $payments = [
            // Admin bought unlimited plan
            [
                'user_id'   => $users['admin']->id,
                'reference' => 'PaystackRef_ADMIN001',
                'amount'    => 12000,
                'plan_name' => 'Monthly Unlimited',
                'status'    => 'success',
                'router_id' => $mainRouter?->id,
                'created_at' => now()->subDays(5),
            ],
            // Active user bought monthly starter
            [
                'user_id'   => $users['active']->id,
                'reference' => 'PaystackRef_CHIOMA01',
                'amount'    => 5000,
                'plan_name' => 'Monthly Starter',
                'status'    => 'success',
                'router_id' => $mainRouter?->id,
                'created_at' => now()->subDays(12),
            ],
            // Family head bought family plan
            [
                'user_id'   => $users['family_head']->id,
                'reference' => 'PaystackRef_BOLA001',
                'amount'    => 8000,
                'plan_name' => 'Family Basic',
                'status'    => 'success',
                'router_id' => $mainRouter?->id,
                'created_at' => now()->subDays(8),
            ],
            // Expired user's old payment
            [
                'user_id'   => $users['expired']->id,
                'reference' => 'PaystackRef_EMEKA01',
                'amount'    => 400,
                'plan_name' => 'Daily Standard',
                'status'    => 'success',
                'created_at' => now()->subDays(4),
            ],
            // Phone-only user bought daily plan
            [
                'user_id'   => $users['phone_with_plan']->id,
                'reference' => 'PaystackRef_PHONE01',
                'amount'    => 400,
                'plan_name' => 'Daily Standard',
                'status'    => 'success',
                'router_id' => $mainRouter?->id,
                'created_at' => now()->subHours(6),
            ],
        ];

        foreach ($payments as $p) {
            Payment::firstOrCreate(['reference' => $p['reference']], $p);
        }

        // Transactions mirror payments
        $transactions = [
            ['user_id' => $users['admin']->id,          'plan_id' => $unlimitedPlan?->id, 'amount' => 12000, 'reference' => 'PaystackRef_ADMIN001', 'status' => 'completed', 'gateway' => 'paystack', 'paid_at' => now()->subDays(5), 'router_id' => $mainRouter?->id],
            ['user_id' => $users['active']->id,          'plan_id' => $monthlyPlan?->id,   'amount' => 5000,  'reference' => 'PaystackRef_CHIOMA01', 'status' => 'completed', 'gateway' => 'paystack', 'paid_at' => now()->subDays(12), 'router_id' => $mainRouter?->id],
            ['user_id' => $users['family_head']->id,     'plan_id' => $familyPlan?->id,    'amount' => 8000,  'reference' => 'PaystackRef_BOLA001',  'status' => 'completed', 'gateway' => 'paystack', 'paid_at' => now()->subDays(8), 'router_id' => $mainRouter?->id],
            ['user_id' => $users['expired']->id,          'plan_id' => $dailyPlan?->id,     'amount' => 400,   'reference' => 'PaystackRef_EMEKA01',  'status' => 'completed', 'gateway' => 'paystack', 'paid_at' => now()->subDays(4)],
            ['user_id' => $users['phone_with_plan']->id, 'plan_id' => $dailyPlan?->id,     'amount' => 400,   'reference' => 'PaystackRef_PHONE01',  'status' => 'completed', 'gateway' => 'paystack', 'paid_at' => now()->subHours(6), 'router_id' => $mainRouter?->id],
        ];

        foreach ($transactions as $t) {
            Transaction::firstOrCreate(['reference' => $t['reference']], $t);
        }

        $this->command->info('Payments & Transactions seeded: ' . count($payments));
    }

    // ── App Settings ─────────────────────────────────────────────

    private function seedAppSettings(): void
    {
        $settings = [
            'wawp_enabled'      => '0',
            'wawp_instance_id'  => '',
            'wawp_access_token' => '',
            'sms_enabled'       => '0',
            'sms_provider'      => 'termii',
            'sms_api_key'       => '',
            'sms_api_secret'    => '',
            'sms_sender_id'     => 'HiFastLink',
            'otp_window_minutes' => '10',
            'otp_max_attempts'   => '3',
        ];

        foreach ($settings as $key => $value) {
            AppSetting::firstOrCreate(['key' => $key], ['value' => $value]);
        }

        $this->command->info('App settings seeded: ' . count($settings));
    }

    // ── OTPs (for testing verify flow) ───────────────────────────

    private function seedOtps(): void
    {
        // Valid OTP (can be used to test verification)
        Otp::create([
            'phone'      => '+2349099887766',
            'otp'        => '123456',
            'expires_at' => now()->addMinutes(10),
        ]);

        // Expired OTP
        Otp::create([
            'phone'      => '+2349099887766',
            'otp'        => '654321',
            'expires_at' => now()->subMinutes(5),
        ]);

        // Already verified OTP
        Otp::create([
            'phone'      => '+2349088776655',
            'otp'        => '111111',
            'expires_at' => now()->addMinutes(5),
            'verified_at' => now()->subMinutes(2),
        ]);

        $this->command->info('OTPs seeded: 3 (valid, expired, verified)');
    }
}
