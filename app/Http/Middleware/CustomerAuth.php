<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CustomerAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Log the authentication attempt
        Log::info('Customer Auth Middleware - Checking authentication:', [
            'url' => $request->url(),
            'is_customer_logged_in' => Auth::guard('customer')->check(),
            'customer_id' => Auth::guard('customer')->id(),
            'session_id' => session()->getId()
        ]);

        if (!Auth::guard('customer')->check()) {
            Log::warning('Customer not authenticated, redirecting to login');
            return redirect()->route('login.form')->with('error', 'Please log in to access this page.');
        }

        // Check if customer account is still active
        $customer = Auth::guard('customer')->user();
        if (!$customer->canLogin()) {
            Log::warning('Customer account cannot login', [
                'customer_id' => $customer->id,
                'is_active' => $customer->is_active,
                'is_restricted' => $customer->is_restricted,
                'deleted_at' => $customer->deleted_at
            ]);
            
            Auth::guard('customer')->logout();
            return redirect()->route('login.form')->with('error', 'Your account is no longer active. Please contact support.');
        }

        return $next($request);
    }
}