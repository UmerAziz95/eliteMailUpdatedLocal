<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserWelcomeMail;
use Carbon\Carbon;

class AuthController extends Controller
{
    // testAdmin
    public function testAdmin()
    {
        // super.admin@email.com delete first
        $user = User::where('email','super.admin@email.com')->first();
        if ($user) {
            $user->delete();
        }
        // Check if the user is already an admin
        if (Auth::check() && Auth::user()->role_id === 'admin') {
            return response()->json(['message' => 'User is already an admin.'], 200);
        }
        $data = [
            'name' => 'Admin User',
            'email'=> 'super.admin@email.com',
            'password' => 'password',
            'phone' => '+1234567890', // Set the phone number
            'role' => 'admin', // Set the role to admin
        ];
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'], // Default role
            'phone' => $data['phone'],
        ]);
        return $user;
    }
    // Show login form
    public function showLoginForm()
    {
        // Check if user is already logged in
        if (Auth::check()) {
            return redirect($this->redirectTo(Auth::user()));
        }
        return view('modules.auth.login');
    }

    // Handle login
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            // Log the user ID and the requested path
            Log::info('User ' . Auth::user()->id . ' logged in at ' . now());
            // Redirect based on role
            return redirect()->intended($this->redirectTo(Auth::user()));
        }

        return back()->withInput()->withErrors(['email' => 'Invalid credentials']);
    }

    // Determine redirection based on user role
    protected function redirectTo($user)
    {
        // dd($user);
        if ($user->role_id == 1 || $user->role_id == 2 || $user->role_id == 5) {
            return route('admin.dashboard');
        } elseif ($user->role_id == 3) {
            return route('customer.dashboard');
        } elseif ($user->role_id == 4) {
            return route('contractor.dashboard');
        } 
    }

    // Handle logout
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('login');
    }

    // Show registration form
    public function showRegisterForm()
    {
        return view('modules.auth.signup');
    }

    // Handle registration refresh
    // public function register(Request $request)
    // {
    //     $data = $request->validate([
    //         'name' => 'required|string|max:255',
    //         'email' => 'required|email|unique:users',
    //         'password' => 'required|min:6|confirmed',
    //         'role' => 'required|in:admin,customer,contractor',
    //     ]);

    //     $user = User::create([
    //         'name' => $data['name'],
    //         'email' => $data['email'],
    //         'password' => Hash::make($data['password']),
    //         'role' => $data['role'],
    //     ]);

    //     Auth::login($user);

    //     return redirect($this->redirectTo($user));
    // }
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
            'role' => 'required|in:admin,customer,contractor',
            'phone' => 'required|regex:/^\+?[0-9]{7,15}$/', // Allow + sign and ensure phone is numeric and between 7 to 15 digits
        ],
        [
            'phone.regex' => 'The phone number must be a valid format (7 to 15 digits).',
            'phone.required' => 'The phone number is required.',
        ]);
        switch ($data['role']) {
            case 'admin':
                $data['role'] = 1;
                break;
            case 'customer':
                $data['role'] = 3;
                break;
            case 'contractor':
                $data['role'] = 4;
                break;
            default:
                $data['role'] = 3; // Default to customer
        }
        // dd($data);
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => $data['role'], // Default role
            'phone' => $data['phone'],
        ]);
        // dd($user);
        // try {
        //     // Send welcome email
        //     // ✅ Queue the welcome email
        //     // Mail::to($user->email)->queue(new UserWelcomeMail($user));
        //     // ✅ Delay until a specific date/time
        //     // add 2 minutes to the current time
        //     $sendDate = Carbon::now()->addMinutes(2);
        //     // add 30 days to the current time
        //     // $sendDate = Carbon::now()->addDays(30);
        //     Mail::to($user->email)->later(
        //         $sendDate,
        //         new UserWelcomeMail($user)
        //     );
        // } catch (\Exception $e) {
        //     Log::error('Failed to send welcome email: ' . $e->getMessage());
        // }
        Auth::login($user);
        $user = User::where('email', $data['email'])->first();
        // dd($user);
        // $user = Auth::user();
        return response()->json([
            'message' => 'User registered successfully!',
            'redirect' => $this->redirectTo($user),
            'user' => $user,
        ], 200);
    }

    // Show forgot password form
    public function showForgotPasswordForm()
    {
        // return view('auth.forgot-password');
        return view('modules.auth.forget_password');
    }

    // Send password reset link
    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);
        Password::sendResetLink($request->only('email'));

        return back()->with('success', 'Password reset link sent to your email.');
    }

    // Show reset password form
    public function showResetPasswordForm($token)
    {
        return view('auth.reset-password', ['token' => $token]);
    }

    // Handle password reset
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required|min:6|confirmed',
            'token' => 'required',
        ]);

        Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->update(['password' => Hash::make($password)]);
            }
        );

        return redirect()->route('login')->with('success', 'Your password has been reset.');
    }

    // Handle password change
    public function changePassword(Request $request)
    {
        $request->validate([
            'oldPassword' => 'required',
            'newPassword' => 'required|min:8|different:oldPassword',
            'confirmPassword' => 'required|same:newPassword'
        ]);

        $user = Auth::user();

        if (!Hash::check($request->oldPassword, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Current password is incorrect'
            ], 400);
        }

        $user->password = Hash::make($request->newPassword);
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Password changed successfully'
        ]);
    }
}
