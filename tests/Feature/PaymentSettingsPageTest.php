<?php

use App\Models\User;
use App\Models\AppSetting;
use Livewire\Livewire;
use App\Filament\Pages\PaymentSettings;

it('mounts with null config and no settings without throwing TypeError', function () {
    config([
        'services.flutterwave.public_key'  => null,
        'services.flutterwave.secret_key'  => null,
        'services.flutterwave.secret_hash' => null,
        'services.flutterwave.base_url'    => null,
        'services.paystack.public_key'     => null,
        'services.paystack.secret_key'     => null,
        'services.paystack.payment_url'    => null,
        'services.payment_gateway'         => null,
    ]);

    $admin = User::factory()->create([
        'email' => 'amazino33@gmail.com',
    ]);

    $this->actingAs($admin);

    Livewire::test(PaymentSettings::class)
        ->assertSuccessful()
        ->assertSet('flw_public_key', '')
        ->assertSet('flw_secret_key', '')
        ->assertSet('paystack_public_key', '')
        ->assertSet('paystack_secret_key', '');
});

it('can save payment settings', function () {
    $admin = User::factory()->create([
        'email' => 'amazino33@gmail.com',
    ]);

    $this->actingAs($admin);

    Livewire::test(PaymentSettings::class)
        ->set('payment_gateway', 'paystack')
        ->set('paystack_public_key', 'pk_test_123456')
        ->set('paystack_secret_key', 'sk_test_654321')
        ->set('paystack_payment_url', 'https://api.paystack.co')
        ->call('saveSettings')
        ->assertHasNoErrors();

    expect(AppSetting::get('payment_gateway'))->toBe('paystack');
    expect(AppSetting::get('paystack_public_key'))->toBe('pk_test_123456');
    expect(AppSetting::get('paystack_secret_key'))->toBe('sk_test_654321');
});
