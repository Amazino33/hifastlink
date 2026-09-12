<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Plan;
use App\Models\RadCheck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_redirect_preserves_captive_state(): void
    {
        $response = $this->get(route('auth.google', [
            'link-login' => 'http://login.wifi/login',
            'mac'        => '11:22:33:44:55:66',
            'router'     => 'main-router',
        ]));

        $response->assertRedirect();
        $this->assertStringContainsString('accounts.google.com', $response->headers->get('Location'));

        $savedState = session('oauth_state');
        $this->assertNotEmpty($savedState);
        $this->assertEquals('http://login.wifi/login', $savedState['link_login']);
        $this->assertEquals('11:22:33:44:55:66', $savedState['mac']);
    }

    public function test_google_callback_creates_user_with_radius_credentials(): void
    {
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn('google-123456');
        $socialiteUser->shouldReceive('getName')->andReturn('Google Test User');
        $socialiteUser->shouldReceive('getEmail')->andReturn('googletest@example.com');

        $provider = Mockery::mock(\Laravel\Socialite\Two\GoogleProvider::class);
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $this->assertAuthenticated();

        $user = User::where('email', 'googletest@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('google-123456', $user->google_id);
        $this->assertNotNull($user->email_verified_at);

        // Check FreeRADIUS radcheck was created
        $this->assertTrue(
            RadCheck::where('username', $user->username)
                ->where('attribute', 'Cleartext-Password')
                ->exists()
        );
    }

    public function test_google_callback_applies_free_trial_when_enabled(): void
    {
        $trialPlan = Plan::create([
            'name'          => 'Google Trial',
            'price'         => 0,
            'data_limit'    => 500,
            'limit_unit'    => 'MB',
            'validity_days' => 1,
            'is_active'     => true,
        ]);

        AppSetting::set('free_wifi_enabled', '1');
        AppSetting::set('free_wifi_plan_id', (string) $trialPlan->id);

        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn('google-trial-id');
        $socialiteUser->shouldReceive('getName')->andReturn('Trial User');
        $socialiteUser->shouldReceive('getEmail')->andReturn('triallover@example.com');

        $provider = Mockery::mock(\Laravel\Socialite\Two\GoogleProvider::class);
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get(route('auth.google.callback'));

        $user = auth()->user();
        $this->assertEquals($trialPlan->id, $user->plan_id);
        $this->assertNotNull($user->free_trial_claimed_at);
    }
}
