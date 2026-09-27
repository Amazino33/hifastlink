<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\RadCheck;
use App\Livewire\AppDashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Livewire\Livewire;
use Tests\TestCase;

class AppDashboardProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('radcheck')) {
            Schema::create('radcheck', function (Blueprint $table) {
                $table->id();
                $table->string('username');
                $table->string('attribute');
                $table->string('op')->default(':=');
                $table->string('value');
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('radreply')) {
            Schema::create('radreply', function (Blueprint $table) {
                $table->id();
                $table->string('username');
                $table->string('attribute');
                $table->string('op')->default(':=');
                $table->string('value');
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('radusergroup')) {
            Schema::create('radusergroup', function (Blueprint $table) {
                $table->id();
                $table->string('username');
                $table->string('groupname');
                $table->integer('priority')->default(1);
                $table->timestamps();
            });
        }
    }

    public function test_user_can_edit_profile_including_username()
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'username' => 'originaluser',
            'phone' => '+2348011112222',
            'email' => 'original@example.com',
        ]);

        Livewire::actingAs($user)
            ->test(AppDashboard::class)
            ->assertSet('profileName', 'Original Name')
            ->assertSet('profileUsername', 'originaluser')
            ->assertSet('profilePhone', '+2348011112222')
            ->assertSet('profileEmail', 'original@example.com')
            ->set('profileName', 'Updated Name')
            ->set('profileUsername', 'updateduser')
            ->set('profilePhone', '+2348099998888')
            ->set('profileEmail', 'updated@example.com')
            ->call('saveProfile')
            ->assertHasNoErrors()
            ->assertDispatched('profile-saved');

        $user->refresh();
        $this->assertEquals('Updated Name', $user->name);
        $this->assertEquals('updateduser', $user->username);
        $this->assertEquals('+2348099998888', $user->phone);
        $this->assertEquals('updated@example.com', $user->email);
    }

    public function test_username_must_be_unique_in_profile_edit()
    {
        User::factory()->create(['username' => 'takenuser']);

        $user = User::factory()->create([
            'username' => 'myuser',
        ]);

        Livewire::actingAs($user)
            ->test(AppDashboard::class)
            ->set('profileUsername', 'takenuser')
            ->call('saveProfile')
            ->assertHasErrors(['profileUsername' => 'unique']);
    }
}
