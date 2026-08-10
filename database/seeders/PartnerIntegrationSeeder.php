<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\PartnerIntegration;
use Illuminate\Database\Seeder;

class PartnerIntegrationSeeder extends Seeder
{
    public function run(): void
    {
        // BasmelCare — migrated from hardcoded NetworkSettings fields
        PartnerIntegration::updateOrCreate(
            ['slug' => 'basmelcare'],
            [
                'name'             => 'BasmelCare Pharmacy',
                'api_url'          => AppSetting::get('basmelcare_api_url', ''),
                'api_key'          => AppSetting::get('basmelcare_api_key', ''),
                'code_field'       => 'invoice_number',
                'primary_color'    => '#059669',
                'icon'             => 'fa-solid fa-mortar-pestle',
                'portal_title'     => 'BasmelCare',
                'portal_subtitle'  => 'Pharmacy Wi-Fi Access',
                'code_label'       => 'Receipt Code',
                'code_placeholder' => 'e.g. K7M9Q2',
                'code_maxlength'   => 30,
                'is_active'        => true,
            ]
        );

        // Brothers Crib — migrated from hardcoded NetworkSettings fields
        PartnerIntegration::updateOrCreate(
            ['slug' => 'brotherscrib'],
            [
                'name'             => 'Brothers Crib',
                'api_url'          => AppSetting::get('gameshop_api_url', ''),
                'api_key'          => AppSetting::get('gameshop_api_key', ''),
                'code_field'       => 'code',
                'primary_color'    => '#7c3aed',
                'icon'             => 'fa-solid fa-gamepad',
                'portal_title'     => 'Brothers Crib',
                'portal_subtitle'  => 'Game Shop Wi-Fi Access',
                'code_label'       => 'Wi-Fi Code',
                'code_placeholder' => 'e.g. A3BK7NMQ',
                'code_maxlength'   => 12,
                'is_active'        => true,
            ]
        );
    }
}
