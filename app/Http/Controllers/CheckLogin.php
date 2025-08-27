<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CheckLogin extends Controller
{
    public function show()
    {
        return view('login');
    }

    public function check(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // Check if user is admin
            if (Auth::user()->isAdmin()) {
                return redirect()->intended(route('dashboard'))->with('success', 'Welcome back!');
            }

            // If not admin, logout and redirect back
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return back()->withErrors(['email' => 'Only admins can log in here.']);
        }

        return back()->withErrors(['email' => 'Invalid credentials.']);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout(); // or just Auth::logout() if using default guard
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Admin logged out successfully.');
    }

    /**
     * Show admin profile page
     */
    public function showProfile()
    {
        // Check if user is admin
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            return redirect()->route('login')->with('error', 'Access denied.');
        }
        
        return view('admin.admin-profile');
    }

    /**
     * Show edit profile form
     */
    public function editProfile()
    {
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            return redirect()->route('login')->with('error', 'Access denied.');
        }
        
        $admin = Auth::user();
        return view('admin.edit-profile', compact('admin'));
    }

    /**
     * Update admin profile - only update fields that exist in database
     */
    public function updateProfile(Request $request)
    {
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            return redirect()->route('login')->with('error', 'Access denied.');
        }
        
        $admin = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $admin->id,
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Only update fields that exist in your database
        $admin->update([
            'name' => $request->name,
            'email' => $request->email,
            // Removed role update for security - admins shouldn't change their own role
        ]);

        return back()->with('success', 'Profile updated successfully!');
    }

    /**
     * Show change password form
     */
    public function showChangePassword()
    {
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            return redirect()->route('login')->with('error', 'Access denied.');
        }
        
        return view('admin.change-password');
    }

    /**
     * Update admin password
     */
    public function updatePassword(Request $request)
    {
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            return redirect()->route('login')->with('error', 'Access denied.');
        }
        
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'password' => 'required|confirmed|min:8',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $admin = Auth::user();

        if (!Hash::check($request->current_password, $admin->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect']);
        }

        $admin->update([
            'password' => Hash::make($request->password)
        ]);

        return back()->with('success', 'Password updated successfully!');
    }

    /**
     * Show permissions page
     */
    public function showPermissions()
    {
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            return redirect()->route('login')->with('error', 'Access denied.');
        }
        
        $admin = Auth::user();
        return view('admin.permissions', compact('admin'));
    }
}