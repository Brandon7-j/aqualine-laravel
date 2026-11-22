<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user());
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $rawLogin = (string) ($request->input('login_id') ?? $request->input('login') ?? '');
        $request->merge(['login_id' => trim($rawLogin)]);

        $credentials =$request->validate([
            'login_id' => 'required|string',
            'password' => 'required|string',
        ], [
            'login_id.required' => 'Please enter your phone number or username.',
            'password.required' => 'Please enter your password.',
        ]);

        $loginInput = trim($credentials['login_id']);

        $user = User::where('username',$loginInput)
            ->orWhere('phone', $loginInput)
            ->orWhere('email', $loginInput)
            ->first();

        if (!$user || !Hash::check($credentials['password'],$user->password_hash ?? '')) {
            return back()
                ->withInput($request->only('login_id'))
                ->withErrors(['login_id' => 'Invalid login credentials.']);
        }

        if (($user->status ?? 'active') !== 'active') {
            return back()
                ->withInput($request->only('login_id'))
                ->withErrors(['login_id' => 'This account is inactive.']);
        }

        if ($user->role === 'owner') {$primaryOwner = User::where('role', 'owner')
                ->orderBy('user_id', 'asc')
                ->first();

            if (!$primaryOwner || (int) $primaryOwner->user_id !== (int)$user->user_id) {
                return back()
                    ->withInput($request->only('login_id'))
                    ->withErrors(['login_id' => 'Access rejected: Only one owner account is permitted in the system.']);
            }
        }

        Auth::login($user);$request->session()->regenerate();

        return $this->redirectByRole($user);
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated =$request->validate([
            'full_name' => 'required|string|max:120',
            'phone'     => 'required|string|max:30|unique:users,phone',
            'email'     => 'nullable|email|max:120|unique:users,email',
            'password'  => 'required|string|min:6|max:100|confirmed',
        ]);

        $phoneClean = trim($validated['phone']);
        $baseUsername = preg_replace('/[^a-zA-Z0-9_]/', '',$phoneClean) ?: 'cust_' . time();
        $username =$baseUsername;
        $i = 1;
        while (User::where('username', $username)->exists()) {$username = $baseUsername . '_' .$i++;
        }

        $user = User::create([
            'full_name'     => trim($validated['full_name']),
            'phone'         => $phoneClean,
            'email'         => !empty($validated['email']) ? trim($validated['email']) : null,
            'username'      => $username,
            'password_hash' => Hash::make($validated['password']),
            'role'          => 'customer',
            'status'        => 'active',
        ]);

        Auth::login($user);$request->session()->regenerate();

        return redirect()->route('customer.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();$request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function redirectByRole(User $user)
    {
        return match ($user->role) {
            'owner'    => redirect()->route('owner.dashboard'),
            'staff'    => redirect()->route('staff.dashboard'),
            default    => redirect()->route('customer.dashboard'),
        };
    }
}