<?php

namespace App\Http\Controllers;

use App\Models\Garment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AccountManagementController extends Controller
{
    public function index()
    {
        $accounts = User::orderByRaw("FIELD(role, 'owner', 'staff', 'customer')")
            ->orderByDesc('user_id')
            ->get();

        $ownerCount = User::where('role', 'owner')->count();

        return view('owner.staff.index', compact('accounts', 'ownerCount'));
    }

    public function storeStaff(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:120',
            'role'      => 'required|in:staff,owner,customer',
            'username'  => 'nullable|string|max:60|unique:users,username',
            'phone'     => 'required|string|max:30|unique:users,phone',
            'email'     => 'nullable|email|max:120|unique:users,email',
            'password'  => 'required|string|min:6|max:100',
        ]);

        if ($validated['role'] === 'owner' && User::where('role', 'owner')->exists()) {
            return back()
                ->withInput()
                ->withErrors(['role' => 'Only one owner account is allowed. Remove any extra owner account first.']);
        }

        if ($validated['role'] !== 'customer' && empty(trim((string) ($validated['username'] ?? '')))) {
            return back()
                ->withInput()
                ->withErrors(['username' => 'Username is required for staff and owner accounts.']);
        }

        $phoneClean = trim($validated['phone']);
        $username = trim((string) ($validated['username'] ?? ''));

        if ($username === '') {
            $baseUsername = preg_replace('/[^a-zA-Z0-9_]/', '', $phoneClean) ?: 'cust_' . time();
            $username = $baseUsername;
            $counter = 1;
            while (User::where('username', $username)->exists()) {
                $username = $baseUsername . '_' . $counter++;
            }
        }

        User::create([
            'full_name'     => trim($validated['full_name']),
            'role'          => $validated['role'],
            'username'      => $username,
            'phone'         => $phoneClean,
            'email'         => !empty($validated['email']) ? trim($validated['email']) : null,
            'password_hash' => Hash::make($validated['password']),
            'status'        => 'active',
        ]);

        return redirect()
            ->route('owner.staff.index')
            ->with('success', ucfirst($validated['role']) . ' account created.');
    }

    public function toggleStaffStatus($userId)
    {
        $account = User::where('user_id', $userId)->firstOrFail();
        $currentUser = Auth::user();

        if ((int) $account->user_id === (int) ($currentUser->user_id ?? 0)) {
            return back()->withErrors(['status' => 'You cannot deactivate your own active session.']);
        }

        $newStatus = ($account->status ?? 'active') === 'active' ? 'inactive' : 'active';
        $account->update(['status' => $newStatus]);

        return redirect()
            ->route('owner.staff.index')
            ->with('success', "Account for {$account->full_name} is now {$newStatus}.");
    }

    public function resetStaffPassword(Request $request, $userId)
    {
        $account = User::where('user_id', $userId)->firstOrFail();

        $validated = $request->validate([
            'password' => 'required|string|min:6|max:100',
        ]);

        $account->update([
            'password_hash' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('owner.staff.index')
            ->with('success', "Password reset for {$account->full_name}.");
    }

    public function destroyAccount($userId)
    {
        $account = User::where('user_id', $userId)->firstOrFail();
        $currentUser = Auth::user();
        $fallbackOwnerId = $currentUser->user_id ?? $currentUser->id ?? null;

        if ((int) $account->user_id === (int) ($fallbackOwnerId ?? 0)) {
            return back()->withErrors(['delete' => 'You cannot delete your currently logged-in owner account.']);
        }

        DB::transaction(function () use ($account, $fallbackOwnerId) {
            if ($fallbackOwnerId) {
                DB::table('service_items')
                    ->where('defined_by', $account->user_id)
                    ->update(['defined_by' => $fallbackOwnerId]);

                DB::table('orders')
                    ->where('staff_id', $account->user_id)
                    ->update(['staff_id' => $fallbackOwnerId]);
            }

            $orderNos = Order::where('customer_id', $account->user_id)->pluck('order_no');
            if ($orderNos->isNotEmpty()) {
                Garment::whereIn('order_no', $orderNos)->delete();
                Order::whereIn('order_no', $orderNos)->delete();
            }

            $allPickups = TrackingController::loadAllPickupRequests();
            $key = (string) $account->user_id;
            if (isset($allPickups[$key])) {
                unset($allPickups[$key]);
                TrackingController::saveAllPickupRequests($allPickups);
            }

            $account->delete();
        });

        return redirect()
            ->route('owner.staff.index')
            ->with('success', "Deleted {$account->role} account: {$account->full_name}.");
    }
}