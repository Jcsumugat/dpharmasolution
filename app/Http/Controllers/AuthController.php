<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use App\Models\CustomerChat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


class AuthController extends Controller
{
    public function showSignupStepOne()
    {
        return view('client.signup1');
    }

    public function handleSignupStepOne(Request $request)
    {
        $validatedData = $request->validate([
            'full_name' => 'required|string|max:100',
            'contact_number' => 'required|string|max:20|unique:customers',
            'email_address' => 'required|string|email|max:50|unique:customers',
            'birthdate' => 'required|date',
            'sex' => 'required|string|max:10',
            'address' => 'required|string|max:255',
        ]);

        // Store validated data in session
        session()->put('registration', $validatedData);

        Log::info('Step 1 data stored in session:', $validatedData);

        return redirect()->route('signup.step_two');
    }

    public function showSignupStepTwo()
    {
        $registration = session('registration');

        if (!$registration) {
            return redirect()->route('signup.step_one')->withErrors(['error' => 'Session expired. Please start over.']);
        }

        return view('client.signup2', ['registration' => $registration]);
    }

    public function handleSignupStepTwo(Request $request)
    {
        // Single validator with all rules
        $validator = Validator::make($request->all(), [
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[A-Z]/',      // At least one uppercase letter
                'regex:/[0-9]/',      // At least one number
            ],
            'terms' => 'required|accepted',
        ]);

        if ($validator->fails()) {
            Log::info('Validation failed:', $validator->errors()->toArray());
            return redirect()->route('signup.step_two')
                ->withErrors($validator)
                ->withInput();
        }

        $registrationData = session('registration');

        Log::info('Step 2 session data:', (array) $registrationData);

        if (!$registrationData) {
            Log::error('No registration data found in session');
            return redirect()->route('signup.step_one')->withErrors(['error' => 'Session expired. Please start over.']);
        }

        try {

            $customer = Customer::create([
                // DON'T set customer_id here - let the model's boot() method handle it
                'full_name' => $registrationData['full_name'],
                'address' => $registrationData['address'],
                'birthdate' => $registrationData['birthdate'],
                'sex' => $registrationData['sex'],
                'email_address' => $registrationData['email_address'],
                'contact_number' => $registrationData['contact_number'],
                'password' => Hash::make($request->password),
                'status' => 'active'
            ]);

            Log::info('Customer created with auto-generated customer_id', [
                'id' => $customer->id,
                'customer_id' => $customer->customer_id,
                'full_name' => $customer->full_name
            ]);

            // CREATE CUSTOMER CHAT RECORD
            CustomerChat::create([
                'customer_id' => $customer->customer_id, // Use the auto-generated string ID
                'email_address' => $customer->email_address,
                'full_name' => $customer->full_name,
                'is_online' => false,
                'last_active' => now(),
                'chat_status' => 'offline'
            ]);

            Log::info('Customer successfully registered with chat record', [
                'id' => $customer->id,
                'customer_id' => $customer->customer_id,
                'full_name' => $customer->full_name,
                'email' => $customer->email_address
            ]);

            session()->forget('registration');

            return redirect()->route('login.form')->with('success', 'Registration successful! You can now log in.');

        } catch (\Exception $e) {
            Log::error('Registration failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('signup.step_two')
                ->withErrors(['error' => 'Registration failed: ' . $e->getMessage()]);
        }
    }

    public function showLoginForm1()
    {
        return view('client.login');
    }

    public function login1(Request $request)
    {
        $credentials = $request->validate([
            'mobile' => 'required|string',
            'password' => 'required|string',
        ]);

        // Find the customer first
        $customer = Customer::where('contact_number', $credentials['mobile'])->first();

        if (!$customer) {
            return back()->withErrors(['mobile' => 'Invalid mobile number or password.'])->withInput();
        }

        // Check if customer account is deleted
        if ($customer->status === 'deleted') {
            return back()->withErrors(['mobile' => 'This account has been deleted. Please contact support.'])->withInput();
        }

        // Check if customer is restricted
        if ($customer->status === 'restricted') {
            return back()->withErrors(['mobile' => 'Your account has been restricted. Please contact support.'])->withInput();
        }

        // Check if customer is deactivated
        if ($customer->status === 'deactivated') {
            return back()->withErrors(['mobile' => 'Your account has been deactivated. Please contact support.'])->withInput();
        }

        // Attempt authentication
        if (Auth::guard('customer')->attempt([
            'contact_number' => $credentials['mobile'],
            'password' => $credentials['password'],
        ])) {
            $request->session()->regenerate(); // Prevent session fixation

            // UPDATE CUSTOMER CHAT STATUS TO ONLINE
            try {
                CustomerChat::where('customer_id', $customer->customer_id)->update([
                    'is_online' => true,
                    'last_active' => now(),
                    'chat_status' => 'online'
                ]);

                Log::info('Customer chat status updated to online', [
                    'customer_id' => $customer->customer_id,
                    'customer_name' => $customer->full_name
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to update customer chat status on login', [
                    'customer_id' => $customer->customer_id,
                    'error' => $e->getMessage()
                ]);
            }

            Log::info('Customer logged in successfully', [
                'id' => $customer->id,
                'customer_id' => $customer->customer_id,
                'customer_name' => $customer->full_name,
                'login_time' => now()
            ]);

            return redirect()->route('home');
        }

        return back()->withErrors(['mobile' => 'Invalid mobile number or password.'])->withInput();
    }

    public function Home()
    {
        if (!Auth::guard('customer')->check()) {
            return redirect()->route('client.login.form')->with('error', 'Unauthorized access');
        }

        return view('client.home');
    }

    public function logout(Request $request)
    {
        // Get the authenticated customer before logging out
        $customer = Auth::guard('customer')->user();

        if ($customer) {
            // UPDATE CUSTOMER CHAT STATUS TO OFFLINE
            try {
                Log::info('Attempting to update chat status to offline for customer', [
                    'customer_id' => $customer->customer_id,
                    'customer_name' => $customer->full_name
                ]);

                $updated = CustomerChat::where('customer_id', $customer->customer_id)->update([
                    'is_online' => false,
                    'last_active' => now(),
                    'chat_status' => 'offline'
                ]);

                Log::info('Customer chat status updated to offline', [
                    'customer_id' => $customer->customer_id,
                    'customer_name' => $customer->full_name,
                    'rows_affected' => $updated,
                    'logout_time' => now()
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to update customer chat status on logout', [
                    'customer_id' => $customer->customer_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        // Logout from customer guard only
        Auth::guard('customer')->logout();
        $request->session()->regenerateToken();

        // Redirect to customer login form
        return redirect()->route('login.form')->with('success', 'You have been logged out successfully.');
    }

    public function show()
    {
        $user = auth()->user();
        return view('client.profile', compact('user'));
    }
}
