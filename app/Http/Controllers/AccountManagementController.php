<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class AccountManagementController extends Controller
{
    private function phoneColumn(): ?string
    {
        foreach (['phone', 'phone_number', 'contact'] as $col) {
            if (Schema::hasColumn('users', $col)) {
                return $col;
            }
        }
        return null;
    }

    private function passwordColumn(): string
    {
        return Schema::hasColumn('users', 'password_hash') ? 'password_hash' : 'password';
    }

    private function activeColumn(): ?string
    {
        if (Schema::hasColumn('users', 'is_active')) {
            return 'is_active';
        }
        if (Schema::hasColumn('users', 'status')) {
            return 'status';
        }
        return null;
    }

    private function isUserActive(User $user, ?string $activeCol): bool
    {
        if ($activeCol === 'is_active') {
            return (bool) $user->is_active;
        }
        if ($activeCol === 'status') {
            return strtolower((string) $user->status) === 'active';
        }
        return true;
    }

    public function index()
    {
        $accounts = User::whereIn('role', ['owner', 'staff'])
            ->orderByRaw("CASE WHEN role = 'owner' THEN 0 ELSE 1 END")
            ->orderBy('full_name')
            ->get();

        $staffMembers = $accounts;
        $hasUsername = Schema::hasColumn('users', 'username');
        $hasEmail = Schema::hasColumn('users', 'email');
        $phoneCol = $this->phoneColumn();
        $activeCol = $this->activeColumn();

        return view('owner.staff.index', compact(
            'accounts',
            'staffMembers',
            'hasUsername',
            'hasEmail',
            'phoneCol',
            'activeCol'
        ));
    }

    public function storeStaff(Request $request)
    {
        $hasUsername = Schema::hasColumn('users', 'username');
        $hasEmail = Schema::hasColumn('users', 'email');
        $phoneCol = $this->phoneColumn();
        $passwordCol = $this->passwordColumn();
        $activeCol = $this->activeColumn();

        $rules = [
            'full_name' => 'required|string|max:100',
            'role' => 'required|in:staff,owner',
            'password' => 'required|string|min:6',
        ];

        if ($hasUsername) {
            $rules['username'] = 'required|string|max:50|unique:users,username';
        }
        if ($phoneCol) {
            $rules['phone'] = "required|string|max:20|unique:users,{$phoneCol}";
        }
        if ($hasEmail) {
            $rules['email'] = 'nullable|email|max:100|unique:users,email';
        }

        $validated = $request->validate($rules);

        $user = new User();
        $user->full_name = trim($validated['full_name']);
        $user->role = $validated['role'];

        if ($hasUsername) {
            $user->username = trim($validated['username']);
        }
        if ($phoneCol) {
            $user->{$phoneCol} = trim($validated['phone']);
        }
        if ($hasEmail) {
            $user->email = !empty($validated['email']) ? trim($validated['email']) : null;
        }

        $user->{$passwordCol} = Hash::make($validated['password']);

        if ($activeCol === 'is_active') {
            $user->is_active = 1;
        } elseif ($activeCol === 'status') {
            $user->status = 'active';
        }

        if (Schema::hasColumn('users', 'created_by')) {
            $user->created_by = Auth::id();
        }

        $user->save();

        $roleLabel = ucfirst($user->role);

        return back()->with('success', "{$roleLabel} account for {$user->full_name} created successfully.");
    }

    public function toggleStaffStatus($userId)
    {
        $user = User::whereIn('role', ['owner', 'staff'])->findOrFail($userId);

        if ((int) $user->user_id === (int) Auth::id()) {
            return back()->withErrors(['staff' => 'You cannot deactivate your own owner account.']);
        }

        $activeCol = $this->activeColumn();
        if (!$activeCol) {
            return back()->withErrors(['staff' => 'Account status toggling is not configured in the users table.']);
        }

        $currentlyActive = $this->isUserActive($user, $activeCol);

        // Prevent locking the system out by deactivating the last active owner
        if ($user->role === 'owner' && $currentlyActive) {
            $activeOwnersQuery = User::where('role', 'owner');
            if ($activeCol === 'is_active') {
                $activeOwnersQuery->where('is_active', 1);
            } else {
                $activeOwnersQuery->where('status', 'active');
            }

            if ($activeOwnersQuery->count() <= 1) {
                return back()->withErrors(['staff' => 'Cannot deactivate the last remaining active owner account.']);
            }
        }

        if ($activeCol === 'is_active') {
            $user->is_active = $currentlyActive ? 0 : 1;
        } else {
            $user->status = $currentlyActive ? 'inactive' : 'active';
        }

        $user->save();

        $newState = $currentlyActive ? 'deactivated' : 'activated';

        return back()->with('success', ucfirst($user->role) . " account for {$user->full_name} has been {$newState}.");
    }

    public function resetStaffPassword(Request $request, $userId)
    {
        $request->validate([
            'new_password' => 'nullable|string|min:6',
        ]);

        $user = User::whereIn('role', ['owner', 'staff'])->findOrFail($userId);
        $newPassword = trim((string) $request->input('new_password')) ?: 'Aqualine@123';

        $passwordCol = $this->passwordColumn();
        $user->{$passwordCol} = Hash::make($newPassword);
        $user->save();

        return back()->with('success', "Password reset for {$user->full_name} ({$user->role}). New password: {$newPassword}");
    }
}