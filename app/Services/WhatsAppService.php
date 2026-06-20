<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Otp;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    public function send(string $phone, string $message): bool
    {
        $sent = $this->sendWhatsApp($phone, $message);

        if (! $sent) {
            $sent = $this->sendSms($phone, $message);
        }

        return $sent;
    }

    public function sendOtp(string $phone): ?string
    {
        if (! $this->checkOtpRateLimit($phone)) {
            return null;
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Otp::create([
            'phone'      => $phone,
            'otp'        => $code,
            'expires_at' => now()->addMinutes(10),
        ]);

        $message = "Your HiFastLink code is: *{$code}*\n\nValid for 10 minutes. Do not share this code.";

        $this->send($phone, $message);

        return $code;
    }

    public function verifyOtp(string $phone, string $code): bool
    {
        $record = Otp::where('phone', $phone)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $record || $record->otp !== $code) {
            return false;
        }

        $record->update(['verified_at' => now()]);

        return true;
    }

    public function checkOtpRateLimit(string $phone): bool
    {
        $windowMinutes = (int) AppSetting::get('otp_window_minutes', 10);
        $maxAttempts   = (int) AppSetting::get('otp_max_attempts', 3);

        $count = Otp::where('phone', $phone)
            ->where('created_at', '>=', now()->subMinutes($windowMinutes))
            ->count();

        return $count < $maxAttempts;
    }

    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            return '+234' . substr($digits, 1);
        }

        if (str_starts_with($digits, '234')) {
            return '+' . $digits;
        }

        return '+' . $digits;
    }

    // ── Private ────────────────────────────────────────────────────

    private function sendWhatsApp(string $phone, string $message): bool
    {
        $enabled    = AppSetting::bool('wawp_enabled', false);
        $instanceId = AppSetting::get('wawp_instance_id', '');
        $token      = AppSetting::get('wawp_access_token', '');

        if (! $enabled || empty($instanceId) || empty($token)) {
            Log::info("[WAWP stub] Would send to {$phone}: {$message}");
            return false;
        }

        $number = preg_replace('/\D/', '', $phone);

        if (strlen($number) === 11 && str_starts_with($number, '0')) {
            $number = '234' . substr($number, 1);
        }

        try {
            $response = Http::get('https://api.wawp.net/v2/send/text', [
                'instance_id'  => $instanceId,
                'access_token' => $token,
                'chatId'       => $number . '@c.us',
                'message'      => $message,
            ]);

            if (! $response->successful()) {
                Log::warning("[WAWP] HTTP error sending to {$phone}: " . $response->body());
                return false;
            }

            $json = $response->json();
            if (
                isset($json['error']) ||
                (isset($json['status']) && $json['status'] === false) ||
                (isset($json['success']) && $json['success'] === false)
            ) {
                Log::warning("[WAWP] Delivery failed for {$phone}: " . $response->body());
                return false;
            }

            Log::info("[WAWP] Sent to {$phone}");
            return true;
        } catch (\Throwable $e) {
            Log::error("[WAWP] Exception sending to {$phone}: " . $e->getMessage());
            return false;
        }
    }

    private function sendSms(string $phone, string $message): bool
    {
        $enabled  = AppSetting::bool('sms_enabled', false);
        $provider = AppSetting::get('sms_provider', '');
        $apiKey   = AppSetting::get('sms_api_key', '');
        $senderId = AppSetting::get('sms_sender_id', 'HiFastLink');

        if (! $enabled || empty($apiKey) || empty($provider)) {
            Log::info("[SMS stub] Would send to {$phone}: {$message}");
            return false;
        }

        $number = preg_replace('/\D/', '', $phone);

        try {
            if ($provider === 'termii') {
                $response = Http::post('https://api.ng.termii.com/api/sms/send', [
                    'to'      => $number,
                    'from'    => $senderId,
                    'sms'     => $message,
                    'type'    => 'plain',
                    'channel' => 'generic',
                    'api_key' => $apiKey,
                ]);

                if (! $response->successful()) {
                    Log::error("[SMS] Termii failed to send to {$phone}: " . $response->body());
                    return false;
                }
            } elseif ($provider === 'bulksms') {
                $response = Http::withBasicAuth($apiKey, AppSetting::get('sms_api_secret', ''))
                    ->post('https://api.bulksms.com/v1/messages', [
                        ['to' => $number, 'body' => $message],
                    ]);

                if (! $response->successful()) {
                    Log::error("[SMS] BulkSMS failed to send to {$phone}: " . $response->body());
                    return false;
                }
            } elseif ($provider === 'kudisms') {
                $response = Http::withToken($apiKey)
                    ->post('https://my.kudisms.net/api/autocomposesms', [
                        'token'   => $apiKey,
                        'gateway' => 2,
                        'data'    => [[$senderId, $number, $message]],
                    ]);

                if (! $response->successful()) {
                    Log::error("[SMS] KudiSMS failed to send to {$phone}: " . $response->body());
                    return false;
                }

                $errorCode = $response->json('error_code');
                if ($errorCode !== '000') {
                    Log::error("[SMS] KudiSMS error {$errorCode} sending to {$phone}: " . $response->body());
                    return false;
                }
            } else {
                Log::warning("[SMS] Unknown provider: {$provider}");
                return false;
            }

            Log::info("[SMS] Sent to {$phone} via {$provider}");
            return true;
        } catch (\Throwable $e) {
            Log::error("[SMS] Exception sending to {$phone}: " . $e->getMessage());
            return false;
        }
    }
}
