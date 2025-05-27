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
use App\Mail\EmailVerificationMail;
use Carbon\Carbon;
use App\Services\ActivityLogService;
use ChargeBee\ChargeBee\Models\Customer;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use App\Models\Plan;
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
    
        $remember = $request->has('remember');
    
        if (Auth::attempt($credentials, $remember)) {
            // Check if the authenticated user's account is inactive
            if (Auth::user()->status == 0) {
                Auth::logout(); // Immediately log them out
                return back()->withErrors(['email' => 'Your account is inactive. Please contact support.']);
            }
    
            $request->session()->regenerate();

            // Log the login activity
            ActivityLogService::log(
                'user_signin',
                'User signed in successfully',
                Auth::user(),
                [
                    'email' => Auth::user()->email,
                    'ip' => $request->ip(),
                    'user_agent' => $request->header('User-Agent')
                ]
            );
    
            // Get the redirect URL based on user role
            $redirectUrl = $this->redirectTo(Auth::user());
            
            // If it's an AJAX request, return JSON
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['redirect' => $redirectUrl]);
            }
            
            // Otherwise redirect to the appropriate dashboard
            return redirect()->intended($redirectUrl);
        }
    
        return back()->withInput()->withErrors(['email' => 'Invalid credentials']);
    }
    

    // Determine redirection based on user role
    protected function redirectTo($user)
    {
        if (!$user) {
            return route('login');
        }

        switch ($user->role_id) {
            case 1:
            case 2:
            case 5:
                return route('admin.dashboard');
            case 3:
                return route('customer.dashboard');
            case 4:
                return route('contractor.dashboard');
            default:
                return route('login');
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

    //     return redirect($this->.redirectTo($user));
    // }
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
            'role' => 'required|in:admin,customer,contractor',
            // 'phone' => 'required|regex:/^\+?[0-9]{7,15}$/',
        ],
        [
            // 'phone.regex' => 'The phone number must be a valid format (7 to 15 digits).',
            // 'phone.required' => 'The phone number is required.',
        ]);
    
        $isCustomer = false;
        switch ($data['role']) {
            case 'admin':
                $data['role'] = 1;
                break;
            case 'customer':
                $data['role'] = 3;
                $isCustomer = true;
                break;
            case 'contractor':
                $data['role'] = 4;
                break;
            default:
                $data['role'] = 3;
                $isCustomer = true;
        }
    
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => $data['role'],
            'status'=>0
            // 'phone' => $data['phone'],
        ]);
        
        // Create Chargebee customer for user with customer role
        if ($isCustomer) {
            try {
                $result = \ChargeBee\ChargeBee\Models\Customer::create([
                    'firstName' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'autoCollection' => 'on',
                ]);
                
                $customer = $result->customer();
                $user->update(['chargebee_customer_id' => $customer->id]);
                
                Log::info('Chargebee customer created for user', [
                    'user_id' => $user->id,
                    'chargebee_customer_id' => $customer->id
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to create Chargebee customer: ' . $e->getMessage(), [
                    'user_id' => $user->id
                ]);
            }
        }
    
        // Log the user registration activity
        ActivityLogService::log(
            'user_signup',
            'New user registered successfully',
            $user, // Performed on
            [
                'email' => $user->email,
                'role_id' => $user->role_id,
                'ip' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'chargebee_customer_id' => $user->chargebee_customer_id,
            ],
            $user->id // Performed by
        );
    
        //send email verification code now 
        // Generate 4-digit random code
    $verificationCode = rand(1000, 9999);

    // Store the code in the user table
    $user->email_verification_code = $verificationCode;
    $user->save();

       // Create encrypted payload
       $payload = $user->email . '/' . $verificationCode . '/' . now()->timestamp;
       $encrypted = Crypt::encryptString($payload);

         // Create the full verification link
         $verificationLink = url('/email_verification/' . $encrypted);
        try {
            Mail::to($user->email)->queue(new EmailVerificationMail($user,$verificationLink));
        } catch (\Exception $e) {
            Log::error('Failed to send email verification code to : '.$user->email . $e->getMessage());
        }
        //send welcome mail
        try {
            Mail::to($user->email)->queue(new UserWelcomeMail($user));
        } catch (\Exception $e) {
            Log::error('Failed to send welcome email: ' . $e->getMessage());
        }
    
        //Auth::login($user);
    
        return response()->json([
            'message' => 'User registered successfully! We have sent you a verification email please verify you email address for smooth login.',
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
        return view('modules.auth.reset_password', ['token' => $token, 'email'=>$email = request('email')]);
    }

    // Handle password reset
    public function resetPassword(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email|exists:users,email',
                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'confirmed',
                    'regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/'
                ],
                'token' => 'required',
            ],
            [
                'password.regex' => 'The password must contain at least one uppercase letter, one number, and one special character.',
            ]);
            
            Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, $password) {
                    $user->update(['password' => Hash::make($password)]);
                }
            );

            return redirect()->route('login')->with('success', 'Your password has been reset.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }
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

    public function showVerifyEmailForm(Request $request,$encrypted){

       return view('modules.auth.verify_email', [
            'encrypted' => $encrypted
        ]);
    }

   // Handle the form submission and verification
        public function VerifyEmailNow(Request $request)
        {
            $request->validate([
                'code' => 'required|array',
                'encrypted' => 'required|string'
            ]);
            if(!$request->encrypted){
                abort(404); 
            }

            try {
                
                $decrypted = Crypt::decryptString($request->encrypted);
                [$email, $expectedCode, $timestamp] = explode('/', $decrypted);

                 $user = User::where('email', $email)->first();

                if (!$user) {
                    return back()->withErrors(['email' => 'User not found.']);
                }

                $inputCode = implode('', $request->code);

                if ($inputCode !== $expectedCode || $inputCode !== $user->email_verification_code) {
                    return back()->withErrors(['code' => 'Invalid verification code.']);
                }

                // Mark user as verified
                $user->email_verified_at = now();
                $user->email_verification_code = null;
                $user->save();
                $encryptedData=$request->encrypted;
                

            return redirect()->to('/plans/public/' . $encryptedData)
                 ->with('success', 'Your email has been verified!');
            } catch (\Exception $e) { 
                Log::error('Failed to verify email: ' . $e->getMessage());
                return back()->withErrors(['error' => 'Verification failed.'. $e->getMessage()]);
            }
        }


        public function viewPublicPlans(Request $request,$encrypted){
         
            $getMostlyUsed = Plan::getMostlyUsed();
            $plans = Plan::with('features')->where('is_active', true)->get();
            $publicPage=true;
            return view('customer.public_outside.plans', compact('plans', 'getMostlyUsed','publicPage','encrypted'));
        }
}