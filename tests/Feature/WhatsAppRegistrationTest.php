<?php

namespace Tests\Feature;

use App\Http\Livewire\PhoneOtpLogin;
use App\Models\AppSetting;
use App\Models\Device;
use App\Models\Otp;
use App\Models\Plan;
use App\Models\RadCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WhatsAppRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_whatsapp_otp(): void
    {
        Livewire::test(PhoneOtpLogin::class)
            ->set('phone', '08012345678')
            ->call('sendOtp')
            ->assertSet('step', 'otp')
            ->assertSet('error', '')
            ->assertSee('A 6-digit verification code has been sent');
    }

    public function test_verifying_otp_creates_user_with_radius_credentials_and_logs_in(): void
    {
        $trialPlan = Plan::create([
            'name'          => 'Welcome Plan',
            'price'         => 0,
            'data_limit'    => 1024,
            'limit_unit'    => 'MB',
            'validity_days' => 1,
            'is_active'     => true,
        ]);
        AppSetting::set('free_wifi_enabled', '1');
        AppSetting::set('free_wifi_plan_id', (string) $trialPlan->id);

        $phone = '08099887766';
        $normalized = User::normalizePhone($phone);

        $component = Livewire::test(PhoneOtpLogin::class)
            ->set('phone', $phone)
            ->call('sendOtp');

        $otp = Otp::where('phone', $normalized)->latest()->value('otp');
        $this->assertNotEmpty($otp);

        $component->set('otp', $otp)
            ->call('verifyOtp');

        $this->assertAuthenticated();

        $user = auth()->user();
        $this->assertNotNull($user);
        $this->assertEquals($normalized, $user->phone);
        $this->assertNotNull($user->phone_verified_at);

        // Verify FreeRADIUS radcheck was created
        $this->assertTrue(
            RadCheck::where('username', $user->username)
                ->where('attribute', 'Cleartext-Password')
                ->exists()
        );
    }

    public function test_new_user_gets_free_trial_when_enabled_in_settings(): void
    {
        $trialPlan = Plan::create([
            'name'          => 'Free Trial 1GB',
            'price'         => 0,
            'data_limit'    => 1024,
            'limit_unit'    => 'MB',
            'validity_days' => 1,
            'is_active'     => true,
        ]);

        AppSetting::set('free_wifi_enabled', '1');
        AppSetting::set('free_wifi_plan_id', (string) $trialPlan->id);

        $phone = '08123456789';
        $normalized = User::normalizePhone($phone);

        $component = Livewire::test(PhoneOtpLogin::class)
            ->set('phone', $phone)
            ->call('sendOtp');

        $otp = Otp::where('phone', $normalized)->latest()->value('otp');
        $this->assertNotEmpty($otp);

        $component->set('otp', $otp)->call('verifyOtp');

        $user = auth()->user();
        $this->assertNotNull($user);
        $this->assertEquals($trialPlan->id, $user->plan_id);
        $this->assertNotNull($user->free_trial_claimed_at);
        $this->assertNotNull($user->plan_expiry);
    }

    public function test_captive_wifi_context_registers_device_and_redirects(): void
    {
        $trialPlan = Plan::create([
            'name'          => 'Captive Trial',
            'price'         => 0,
            'data_limit'    => 1024,
            'limit_unit'    => 'MB',
            'validity_days' => 1,
            'is_active'     => true,
        ]);
        AppSetting::set('free_wifi_enabled', '1');
        AppSetting::set('free_wifi_plan_id', (string) $trialPlan->id);

        $phone = '08055443322';
        $normalized = User::normalizePhone($phone);
        $linkLogin = 'http://login.wifi/login';
        $mac = 'AA:BB:CC:11:22:33';

        $component = Livewire::test(PhoneOtpLogin::class, [
            'linkLogin' => $linkLogin,
            'mac'       => $mac,
        ])
            ->set('phone', $phone)
            ->call('sendOtp');

        $otp = Otp::where('phone', $normalized)->latest()->value('otp');
        $this->assertNotEmpty($otp);

        $component->set('otp', $otp)->call('verifyOtp');

        // Check device was registered
        $this->assertTrue(Device::where('mac', $mac)->exists());
    }
}
