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
        $request->validate([
            'login_id' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginId = trim($request->login_id);

        $user = User::where('phone', $loginId)
            ->orWhere('username', $loginId)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            return back()
                ->withInput($request->only('login_id'))
                ->withErrors(['login_id' => 'Invalid login credentials.']);
        }

        if (!$user->isActive()) {
            return back()
                ->withInput($request->only('login_id'))
                ->withErrors(['login_id' => 'This account is inactive.']);
        }

        $user->last_login_at = now();
        $user->save();

        Auth::login($user);
        $request->session()->regenerate();

        return $this->redirectByRole($user);
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user());
        }
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:100',
            'phone' => 'required|string|max:20|unique:users,phone',
            'email' => 'nullable|email|max:100|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'full_name' => trim($validated['full_name']),
            'phone' => preg_replace('/\s+/', '', $validated['phone']),
            'email' => $validated['email'] ?? null,
            'password_hash' => Hash::make($validated['password']),
            'role' => 'customer',
            'status' => 'active',
            'last_login_at' => now(),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('customer.dashboard')
            ->with('success', 'Welcome to Aqualine! Your account is ready.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    private function redirectByRole(User $user)
    {
        return match ($user->role) {
            'owner' => redirect()->route('owner.dashboard'),
            'staff' => redirect()->route('staff.dashboard'),
            'customer' => redirect()->route('customer.dashboard'),
            default => redirect()->route('login'),
        };
    }
}