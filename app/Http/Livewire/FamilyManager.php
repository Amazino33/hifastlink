<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Filament\Notifications\Notification;

class FamilyManager extends Component
{
    public $name;
    public $username;
    public $password;
    public $existingUsername;

    protected $rules = [
        'name' => 'required|string|max:255',
        'username' => 'required|string|max:255|unique:users,username',
        'password' => 'required|string|min:8',
    ];

    public function render()
    {
        if (!Auth::user()->is_family_admin) {
            abort(403, 'Access denied. Family admin required.');
        }

        $familyMembers = Auth::user()->children;

        return view('livewire.family-manager', [
            'familyMembers' => $familyMembers,
        ]);
    }

    public function addMember()
    {
        $this->validate();

        $user = Auth::user();

        if ($user->children()->count() >= $user->family_limit) {
            Notification::make()
                ->title('Family Limit Reached')
                ->body('You have reached your family member limit.')
                ->danger()
                ->send();

            return;
        }

        $newMember = User::create([
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->username . '@family.local', // dummy email
            'password' => Hash::make($this->password),
            'radius_password' => $this->password, // plain text for RADIUS
            'parent_id' => $user->id,
            'plan_id' => $user->plan_id,
            'plan_expiry' => $user->plan_expiry,
            'plan_started_at' => $user->plan_started_at,
        ]);

        // Trigger observer to sync RADIUS
        $newMember->save();

        Notification::make()
            ->title('Family Member Added')
            ->body("{$this->name} has been added to your family.")
            ->success()
            ->send();

        // Reset form
        $this->reset(['name', 'username', 'password']);
    }

    public function addExistingMember()
    {
        $this->validate([
            'existingUsername' => 'required|string|exists:users,username',
        ]);

        $user = Auth::user();
        $foundUser = User::where('username', $this->existingUsername)->first();

        // Constraint 1: Cannot add self
        if ($foundUser->id === $user->id) {
            Notification::make()
                ->title('Invalid Action')
                ->body('You cannot add yourself to your family.')
                ->danger()
                ->send();
            return;
        }

        // Constraint 2: User cannot already have a parent
        if ($foundUser->parent_id) {
            Notification::make()
                ->title('User Already in Family')
                ->body('This user is already part of another family.')
                ->danger()
                ->send();
            return;
        }

        // Constraint 3: Check family limit
        if ($user->children()->count() >= $user->family_limit) {
            Notification::make()
                ->title('Family Limit Reached')
                ->body('You have reached your family member limit.')
                ->danger()
                ->send();
            return;
        }

        // Update the found user
        $foundUser->parent_id = $user->id;
        $foundUser->plan_id = $user->plan_id;
        $foundUser->plan_expiry = $user->plan_expiry;
        $foundUser->plan_started_at = $user->plan_started_at;
        $foundUser->data_used = 0; // Reset usage
        $foundUser->save(); // Trigger observer to sync RADIUS

        Notification::make()
            ->title('Existing User Added')
            ->body("{$foundUser->name} has been added to your family.")
            ->success()
            ->send();

        // Reset form
        $this->reset(['existingUsername']);
    }

    public function removeMember($memberId)
    {
        $member = User::find($memberId);

        if ($member && $member->parent_id === Auth::id()) {
            $member->delete(); // Soft delete instead of hard delete

            Notification::make()
                ->title('Family Member Removed')
                ->body("{$member->name} has been removed from your family.")
                ->success()
                ->send();
        }
    }
}