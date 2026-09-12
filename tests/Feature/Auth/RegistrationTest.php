<?php

test('registration endpoint redirects to unified onboarding', function () {
    $response = $this->get('/register');

    $response->assertRedirect(route('login', ['tab' => 'whatsapp']));
});

test('legacy registration post redirects to login', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('login'));
});
