<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Device;
use App\Models\RadCheck;
use App\Models\RadReply;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GracePassService
{
    /** Grace pass RADIUS username prefix. */
    protected const USER_PREFIX = 'grace_';

    public static function isEnabled(): bool
    {
        return AppSetting::bool('grace_pass_enabled', false);
    }

    public static function getDurationMinutes(): int
    {
        return max(1, (int) AppSetting::get('grace_pass_duration_minutes', 5));
    }

    public static function getDataLimitMb(): int
    {
        return max(0, (int) AppSetting::get('grace_pass_data_limit_mb', 50));
    }

    public static function getSpeedUpload(): int
    {
        return max(0, (int) AppSetting::get('grace_pass_speed_upload', 1024));
    }

    public static function getSpeedDownload(): int
    {
        return max(0, (int) AppSetting::get('grace_pass_speed_download', 2048));
    }

    public static function getCooldownHours(): int
    {
        return max(0, (int) AppSetting::get('grace_pass_cooldown_hours', 24));
    }

    public static function getButtonText(): string
    {
        return (string) AppSetting::get('grace_pass_button_text', 'Get 5 Mins Free Internet');
    }

    public static function getInstruction(): string
    {
        return (string) AppSetting::get('grace_pass_instruction', '');
    }

    /**
     * Attempt to grant a grace pass to the given device.
     *
     * @return array{success: bool, error?: string, username?: string, password?: string, duration?: int}
     */
    public static function claim(?string $mac, ?string $ip = null, ?string $router = null): array
    {
        if (! self::isEnabled()) {
            return ['success' => false, 'error' => 'Grace Pass is currently disabled.'];
        }

        if (blank($mac)) {
            return ['success' => false, 'error' => 'Your device MAC address was not detected.'];
        }

        $normalizedMac = self::normalizeMac($mac);
        $username      = self::USER_PREFIX . strtolower($normalizedMac);

        $existing  = RadCheck::where('username', $username)
            ->where('attribute', 'Cleartext-Password')->first();
        $expiryRow = RadCheck::where('username', $username)
            ->where('attribute', 'Expiration')->first();

        // Active pass still valid → reuse it (e.g. user refreshed the page).
        if ($existing && $expiryRow && Carbon::parse($expiryRow->value)->isFuture()) {
            self::upsertDevice($mac, $ip, $router, $username);

            return [
                'success'  => true,
                'username' => $username,
                'password' => $existing->value,
                'duration' => max(1, (int) now()->diffInMinutes(Carbon::parse($expiryRow->value), false)),
            ];
        }

        // Cooldown: same MAC can't claim again within N hours of the previous claim.
        $cooldownHours = self::getCooldownHours();
        if ($cooldownHours > 0) {
            $cooldownKey = 'grace_pass_last_claim_' . $normalizedMac;
            $lastClaim   = AppSetting::get($cooldownKey);

            if ($lastClaim) {
                try {
                    $availableAt = Carbon::parse($lastClaim)->addHours($cooldownHours);
                } catch (\Throwable) {
                    $availableAt = null;
                }

                if ($availableAt && $availableAt->isFuture()) {
                    $mins = (int) now()->diffInMinutes($availableAt, false);
                    $hrs  = max(1, (int) ceil($mins / 60));

                    return [
                        'success' => false,
                        'error'   => "You've already used your free Grace Pass. Try again in about {$hrs} hour(s).",
                    ];
                }
            }
        }

        // Issue a fresh pass.
        $duration    = self::getDurationMinutes();
        $dataLimitMb = self::getDataLimitMb();
        $upKbps      = self::getSpeedUpload();
        $downKbps    = self::getSpeedDownload();
        $password    = Str::random(12);
        $expiresAt   = now()->addMinutes($duration);

        RadCheck::updateOrCreate(
            ['username' => $username, 'attribute' => 'Cleartext-Password'],
            ['op' => ':=', 'value' => $password]
        );

        RadCheck::updateOrCreate(
            ['username' => $username, 'attribute' => 'Simultaneous-Use'],
            ['op' => ':=', 'value' => '1']
        );

        RadCheck::updateOrCreate(
            ['username' => $username, 'attribute' => 'Expiration'],
            ['op' => ':=', 'value' => $expiresAt->format('d M Y H:i')]
        );

        if ($upKbps || $downKbps) {
            RadReply::updateOrCreate(
                ['username' => $username, 'attribute' => 'Mikrotik-Rate-Limit'],
                ['op' => ':=', 'value' => $upKbps . 'k/' . $downKbps . 'k']
            );
        }

        if ($dataLimitMb > 0) {
            $limitBytes = $dataLimitMb * 1048576;
            $gigawords  = (int) ($limitBytes / 4294967296);
            $totalLimit = (int) ($limitBytes - ($gigawords * 4294967296));

            RadReply::updateOrCreate(
                ['username' => $username, 'attribute' => 'Mikrotik-Total-Limit'],
                ['op' => ':=', 'value' => (string) $totalLimit]
            );

            if ($gigawords > 0) {
                RadReply::updateOrCreate(
                    ['username' => $username, 'attribute' => 'Mikrotik-Total-Limit-Gigawords'],
                    ['op' => ':=', 'value' => (string) $gigawords]
                );
            }
        }

        self::upsertDevice($mac, $ip, $router, $username);

        AppSetting::set('grace_pass_last_claim_' . $normalizedMac, now()->toIso8601String());

        Log::info('GracePass granted', [
            'mac'        => $normalizedMac,
            'username'   => $username,
            'router'     => $router,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);

        return [
            'success'  => true,
            'username' => $username,
            'password' => $password,
            'duration' => $duration,
        ];
    }

    protected static function normalizeMac(string $mac): string
    {
        return strtoupper(str_replace([':', '-', '.'], '', trim($mac)));
    }

    /**
     * Persist the grace-pass username on the Device row so CaptiveAuth
     * can silently re-bridge on future connections while the pass is valid.
     */
    protected static function upsertDevice(?string $mac, ?string $ip, ?string $router, string $username): void
    {
        if (! $mac) {
            return;
        }

        try {
            $device = Device::where('mac', strtoupper($mac))->first();
            $meta   = $device && is_array($device->meta) ? $device->meta : [];

            $meta['grace_pass_username'] = $username;
            if ($router) {
                $meta['grace_pass_router'] = $router;
            }

            Device::updateOrCreate(
                ['mac' => strtoupper($mac)],
                [
                    'ip'           => $ip,
                    'last_seen'    => now(),
                    'is_connected' => true,
                    'meta'         => $meta,
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('GracePass device upsert failed: ' . $e->getMessage());
        }
    }
}