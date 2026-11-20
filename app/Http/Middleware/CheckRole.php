<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        if (!$user->isActive()) {
            Auth::logout();
            $request->session()->invalidate();
            return redirect()->route('login')->withErrors([
                'login_id' => 'Your account has been deactivated. Contact the Owner.',
            ]);
        }

        if (!$user->hasRole($role)) {
            abort(403, 'Unauthorized access for your role.');
        }

        return $next($request);
    }
}
