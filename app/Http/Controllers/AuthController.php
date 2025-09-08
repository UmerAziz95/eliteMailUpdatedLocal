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
use App\Mail\UserRegisteredMail;
use Carbon\Carbon;
use App\Services\ActivityLogService;
use ChargeBee\ChargeBee\Models\Customer;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Support\Facades\Validator;
use App\Models\Onboarding;
use App\Models\DiscordUserLoginSession;
class AuthController extends Controller
{
    // testAdmin
    public function testAdmin()
    {
        //super.admin@email.com delete first
        $user = User::where('email','super.admin@email.com')->first();
        if ($user) {
            $user->delete();
        }
        //Check if the user is already an admin
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
    //Show login form
    public function showLoginForm(Request $request)
    {
       $type = $request->query('type');
       if(isset($type)){
           $discord_setting_page_session= DiscordUserLoginSession::create([
                'discord_setting_page_id' => $type
            ]);
        }
        else{
            $discord_setting_page_session = null;
        }
      
        // Check if user is already logged in
       if (Auth::check()) {
          Auth::logout(); // Log out the user if already logged in
       }
        return view('modules.auth.login',compact('discord_setting_page_session'));
    }

    //Handle login
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'discord_setting_page_id'=> 'nullable|exists:discord_user_login_sessions,discord_setting_page_id'
        ]);
    
         $remember = $request->has('remember');
         $type_id=session()->get('iam_discounted_user'); // 
       
         
        // First login attempt - check credentials before any other validations
        $credentials = $request->only('email', 'password');
        
        if (Auth::attempt($credentials, $remember)) {
            // Store successful login attempt temporarily
            $loginSuccessful = true;
        } else {
            $loginSuccessful = false;
        }
        // check session then forget it
        if (session()->has('temp_user_custom_checkout')) {
            session()->forget('temp_user_custom_checkout');
        }
        // create temp user session custom checkout
        session()->put('temp_user_custom_checkout', Auth::user());
        // immediately logout to perform additional checks
        Auth::logout();
        // If login failed, return early with error
        if (!$loginSuccessful) {
            return back()->withInput()->withErrors(['email' => 'Invalid credentials']);
        }
        
        // // Logout immediately to perform additional checks
        // Auth::logout();

        $userCheck=User::where('email',$request->email)->first();
        if(!$userCheck){
             return back()->withErrors(['email' => 'Account does not exist!']);
        }
        
        //
        if($userCheck->status==0){
            // Generate new verification code
            $verificationCode = rand(1000, 9999);
            $userCheck->email_verification_code = $verificationCode;
            $userCheck->save();

            // Create verification link

            $payload = $userCheck->email . '/' . $verificationCode . '/' . now()->timestamp;
            $encrypted = Crypt::encryptString($payload);
           if ($userCheck->type == "discounted") {
                if (isset($type_id)) {
                    $verificationLink = url("/discounted/user/verify/{$encrypted}/{$type_id}");
                } 
                else { 
                    if(isset($request->discord_setting_page_id)){
                        $discord_setting_page_id = $request->discord_setting_page_id;
                    }else{
                        
                        $discord_setting_page_id = DiscordUserLoginSession::orderBy('id', 'desc')
                        ->pluck('discord_setting_page_id')
                        ->first();
                    }
                    
                    return redirect()->to("/discounted/user/verify/{$encrypted}/{$discord_setting_page_id}");
                }
            } 
            else {
               
                return redirect()->to("/plans/public/{$encrypted}");
            }

            // Send verification email
            try {
                Mail::to($userCheck->email)->queue(new EmailVerificationMail($userCheck, $verificationLink));
                return back()->withErrors(['email' => 'Please verify your account. We have sent you a verification link to your email. Please check your inbox to continue.']);
            } catch (\Exception $e) {
                Log::error('Failed to send email verification code: '.$userCheck->email.' '.$e->getMessage());
                return back()->withErrors(['email' => 'Please verify your account. Unable to send verification email at this time. Please try again later.']);
            }
        }
        // Don't check static link here - it should be checked after authentication
        // Check if user came from a static link BEFORE redirecting to public plans
        if (session('static_link_hit') && session('static_plan_data')) {
            // dd('here');
            // Create encrypted data for the user
            $payload = $userCheck->email . '/' . rand(1000, 9999) . '/' . now()->timestamp;
            $encrypted = Crypt::encryptString($payload);
            
            // Remove the flag to prevent repeated redirects
            // session()->forget('static_link_hit');
            return redirect()->to('/static-plans/' . $encrypted);
        }
        $userSubsc=Subscription::where('user_id',$userCheck->id)->first();
       if (!$userSubsc && $userCheck->role_id == 3) {
            
            
            // If user has no subscription and is a customer, redirect to plans/public with encrypted user info
            $payload = $userCheck->email . '/' . rand(1000, 9999) . '/' . now()->timestamp;
            $encrypted = Crypt::encryptString($payload);

            if ($userCheck->type == "discounted") {
                if (isset($type_id)) {
                    return redirect()->to('/discounted/user/verify/' . $encrypted . '/' . $type_id);
                }
                 else {
                    if (isset($request->discord_setting_page_id)) {
                        $discord_setting_page_id = $request->discord_setting_page_id;
                    } else {
                        $discord_setting_page_id = DiscordUserLoginSession::orderBy('id', 'desc')
                            ->pluck('discord_setting_page_id')
                            ->first();
                    }

                    $verificationLink = url("/discounted/user/verify/{$encrypted}/{$discord_setting_page_id}");
                    return redirect()->to('/discounted/user/verify/' . $encrypted . '/' . $discord_setting_page_id);
                }
            }
            else {
                if($type_id !==null){
                     return redirect()->to('/discounted/user/verify/' . $encrypted . '/' . $type_id);
                }

                return redirect()->to('/plans/public/' . $encrypted);
            }
        }
        else{
           
            
            if($userSubsc && $userCheck->role_id == 3){
                if($type_id){
                     $payload = $userCheck->email . '/' . rand(1000, 9999) . '/' . now()->timestamp;
                     $encrypted = Crypt::encryptString($payload); 
                     return redirect()->to('/discounted/user/verify/' . $encrypted . '/' . $type_id);

                }
            }
        }

        if (Auth::attempt($credentials, $remember)) {
            // Check if the authenticated user's account is inactive
            if (Auth::user()->status == 0 && $userCheck->role_id == 3 ) {
                Auth::logout(); // Immediately log them out
                return back()->withErrors(['email' => 'Your account is inactive. Please verify your account using the verification email we sent to your registered email address.']);
                // return back()->withErrors(['email' => 'Please verify your account to continue. We\'ve sent a verification link to your email address.']);
            }
            if (Auth::user()->status == 0 && $userCheck->role_id != 3 ){
                // If the user is active, proceed with the login
                  Auth::logout(); // Immediately log them out
                  return back()->withErrors(['email' => 'Your account is inactive. Please contact support for assistance.']);
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

        // Check if user came from a static link - this takes priority for customers
        // if (session('static_link_hit') && session('static_plan_data') && $user->role_id == 3) {
        //     // Remove the flag to prevent repeated redirects
        //     session()->forget('static_link_hit');
        //     return route('static-plans');
        // }

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

    // Handle static plans registration
    public function registerForStaticPlans(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'role' => 'required|in:customer',
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/'
            ],
            'password_confirmation' => 'required',
        ], [
            'password.required' => 'Password is required.',
            'password.string' => 'Password must be a valid string.',
            'password.min' => 'Password must be at least 8 characters long.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password.regex' => 'Password must contain at least one uppercase letter, one number, and one special character.',
            'password_confirmation.required' => 'Password confirmation is required.',
        ]);

        // Check if the user is already registered
        $existingUser = User::where('email', $data['email'])->first();
        if ($existingUser && $existingUser->status == 0) {
            $verificationCode = rand(1000, 9999);
            $existingUser->email_verification_code = $verificationCode;
            $existingUser->save();

            $payload = $existingUser->email . '/' . $verificationCode . '/' . now()->timestamp;
            $encrypted = Crypt::encryptString($payload);
            $verificationLink = url("/static-plans/{$encrypted}");

            try {
                Mail::to($existingUser->email)->queue(new EmailVerificationMail($existingUser, $verificationLink));
            } catch (\Exception $e) {
                Log::error('Failed to send email verification code: '.$existingUser->email.' '.$e->getMessage());
            }

            return response()->json([
                'message' => 'We have sent you a verification link to your email. Please check your inbox to continue. Thank you!',
            ], 200);
        }
        
        if ($existingUser) {
            $userSubs = Subscription::where('user_id', $existingUser->id)->first();
            if ($userSubs) {
                return response()->json([
                    'message' => 'Account already exists. Please login.',
                ], 403);
            } else {
                // Email verification code
                $verificationCode = rand(1000, 9999);
                $existingUser->email_verification_code = $verificationCode;
                $existingUser->save();

                $payload = $existingUser->email . '/' . $verificationCode . '/' . now()->timestamp;
                $encrypted = Crypt::encryptString($payload);
                $verificationLink = url("/static-plans/{$encrypted}");

                try {
                    Mail::to($existingUser->email)->queue(new EmailVerificationMail($existingUser, $verificationLink));
                } catch (\Exception $e) {
                    Log::error('Failed to send email verification code: '.$existingUser->email.' '.$e->getMessage());
                }

                return response()->json([
                    'message' => 'We have sent you a verification link to your email. Please check your inbox to continue. Thank you!',
                    'redirect' => '/static-plans/' . $encrypted,
                    'user' => $existingUser,
                    'verificationLink' => $verificationLink
                ], 200);
            }
        }

        // Create new user
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => 3,
            'status' => 0,
            'type' => 'static'
        ]);

        // set temp_user_custom_checkout
        if(session()->has('temp_user_custom_checkout')) {
            session()->forget('temp_user_custom_checkout');
        }
        session()->put('temp_user_custom_checkout', $user);

        // Optional: create Chargebee customer
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

        // Log activity
        ActivityLogService::log(
            'user_signup',
            'New user registered successfully for static plans',
            $user,
            [
                'email' => $user->email,
                'role_id' => $user->role_id,
                'ip' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'chargebee_customer_id' => $user->chargebee_customer_id,
            ],
            $user->id
        );

        // Email verification code
        $verificationCode = rand(1000, 9999);
        $user->email_verification_code = $verificationCode;
        $user->save();

        $payload = $user->email . '/' . $verificationCode . '/' . now()->timestamp;
        $encrypted = Crypt::encryptString($payload);
        $verificationLink = url("/static-plans/{$encrypted}");

        // Send email to user
        try {
            Log::info("sending email to user: ".$user->email);
            Mail::to($user->email)->queue(new EmailVerificationMail($user, $verificationLink));
        } catch (\Exception $e) {
            Log::error('Failed to send email verification code: '.$user->email.' '.$e->getMessage());
        }

        // Send email to super admin
        try {
            Log::info("sending email to super admins");
            $superAdmins = User::whereIn('role_id', [1])->get();
            foreach ($superAdmins as $superAdmin) {
                Mail::to($superAdmin->email)->queue(new UserRegisteredMail($superAdmin));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send registration email to admin. Error: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'User registered successfully! We have sent you a verification link to your email. Please check your inbox to continue. Thank you!',
            'redirect' => '/static-plans/' . $encrypted,
            'user' => $user,
            'verificationLink' => $verificationLink
        ], 200);
    }
    public function register(Request $request)
    {
        // check session for static plan
        // Check if static plan session is set
        if (session('static_link_hit')) {
            return $this->registerForStaticPlans($request);
        }
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'role' => 'required|in:customer',
            'password' => [
            'required',
            'string',
            'min:8',
            'confirmed',
            'regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/'
            ],
            'password_confirmation' => 'required',
        ], [
            'password.required' => 'Password is required.',
            'password.string' => 'Password must be a valid string.',
            'password.min' => 'Password must be at least 8 characters long.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password.regex' => 'Password must contain at least one uppercase letter, one number, and one special character.',
            'password_confirmation.required' => 'Password confirmation is required.',
        ]);

        $type_id=session()->get('iam_discounted_user');
        
        // Check if the user is already registered
        $existingUser = User::where('email', $data['email'])->first();
        if ($existingUser && $existingUser->status == 0) {
              $verificationCode = rand(1000, 9999);
                $existingUser->email_verification_code = $verificationCode;
                $existingUser->save();

                $payload = $existingUser->email . '/' . $verificationCode . '/' . now()->timestamp;
                $encrypted = Crypt::encryptString($payload);
               if ($existingUser->type == "discounted") {
                    if (isset($type_id)) {
                        $verificationLink = url("/discounted/user/verify/{$encrypted}/{$type_id}");
                    }
                     else { 
                    if(isset($request->discord_setting_page_id)){
                        $discord_setting_page_id = $request->discord_setting_page_id;
                    }else{
                        
                      $discord_setting_page_id = DiscordUserLoginSession::orderBy('id', 'desc')
                        ->pluck('discord_setting_page_id')
                        ->first();
                    }
                    $verificationLink = url("/discounted/user/verify/{$encrypted}/{$discord_setting_page_id}");
                   }
                } else {
                    $verificationLink = url("/plans/public/{$encrypted}");
                }

                try {
                    Mail::to($existingUser->email)->queue(new EmailVerificationMail($existingUser, $verificationLink));
                } catch (\Exception $e) {
                    Log::error('Failed to send email verification code: '.$existingUser->email.' '.$e->getMessage());
                }
            return response()->json([
                'message' => 'We have sent you a verification link to your email. Please check your inbox to continue. Thank you!',
            ], 200);
        }
        
        if ($existingUser) {
            $userSubs=Subscription::where('user_id',$existingUser->id)->first();
            if($userSubs){
                return response()->json([
                    'message' => 'Account already exists. Please login.',
                ], 403);
            }
            else {
                // Email verification code
                $verificationCode = rand(1000, 9999);
                $existingUser->email_verification_code = $verificationCode;
                if(!$type_id || $type_id == null){
                    $existingUser->type=null;
                }else{
                    $existingUser->type = "discounted";
                }
                $existingUser->save();

                $payload = $existingUser->email . '/' . $verificationCode . '/' . now()->timestamp;
                $encrypted = Crypt::encryptString($payload);

               if ($existingUser->type == "discounted") {
                    if (isset($type_id)) {
                        $verificationLink = url("/discounted/user/verify/{$encrypted}/{$type_id}");

                    } 
                 else { 
                    if(isset($request->discord_setting_page_id)){
                        $discord_setting_page_id = $request->discord_setting_page_id;
                    }else{
                        
                        $discord_setting_page_id = DiscordUserLoginSession::orderBy('id', 'desc')
                        ->pluck('discord_setting_page_id')
                        ->first();
                    }
                    $verificationLink = url("/discounted/user/verify/{$encrypted}/{$discord_setting_page_id}");
                   }
                }
                 else {
                    $verificationLink = url("/plans/public/{$encrypted}");
                }

                try {
                    Mail::to($existingUser->email)->queue(new EmailVerificationMail($existingUser, $verificationLink));
                } catch (\Exception $e) {
                    Log::error('Failed to send email verification code: '.$existingUser->email.' '.$e->getMessage());
                }

                return response()->json([
                    'message' => 'We have sent you a verification link to your email. Please check your inbox to continue. Thank you!',
                    'redirect' => $this->redirectTo($existingUser),
                    'user' => $existingUser,
                    'verificationLink'=>$verificationLink
                ], 200);
            }
        
        }



        // Create new user
        
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' =>3,
            'status' => 0,
            'type'=>$type_id ? "discounted":null
        ]);
        // set temp_user_custom_checkout
        if(session()->has('temp_user_custom_checkout')) {
            session()->forget('temp_user_custom_checkout');
        }
        session()->put('temp_user_custom_checkout', $user);
        // Optional: create Chargebee customer
        
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

        // Log activity
        ActivityLogService::log(
            'user_signup',
            'New user registered successfully',
            $user,
            [
                'email' => $user->email,
                'role_id' => $user->role_id,
                'ip' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'chargebee_customer_id' => $user->chargebee_customer_id,
            ],
            $user->id
        );

        // Email verification code
        $verificationCode = rand(1000, 9999);
        $user->email_verification_code = $verificationCode;
        $user->save();

        $payload = $user->email . '/' . $verificationCode . '/' . now()->timestamp;
        $encrypted = Crypt::encryptString($payload);
        $verificationLink="";
         if ($user->type == "discounted") {
            if (isset($type_id)) {
                $verificationLink = url("/discounted/user/verify/{$encrypted}/{$type_id}");
            } 
             else { 
                    if(isset($request->discord_setting_page_id)){
                        $discord_setting_page_id = $request->discord_setting_page_id;
                    }else{
                        
                       $discord_setting_page_id = DiscordUserLoginSession::orderBy('id', 'desc')
                        ->pluck('discord_setting_page_id')
                        ->first();
                    }
                    $verificationLink = url("/discounted/user/verify/{$encrypted}/{$discord_setting_page_id}");
                }
            } else {
                $verificationLink = url("/plans/public/{$encrypted}");
            }

//to user
        try {
            Log::info("sending email to user: ".$user->email);
            Mail::to($user->email)->queue(new EmailVerificationMail($user, $verificationLink));
        } catch (\Exception $e) {
            Log::error('Failed to send email verification code: '.$user->email.' '.$e->getMessage());
        }

        //to super admin
        try {
        Log::info("sending email to super admins");
        $superAdmins = User::whereIn('role_id', [1])->get(); // Get both role 1 & 2
        foreach ($superAdmins as $superAdmin) {
            Mail::to($superAdmin->email)->queue(new UserRegisteredMail($superAdmin));
        }
        } catch (\Exception $e) {
            Log::error('Failed to send registration email to admin. Error: ' . $e->getMessage());
        }


        return response()->json([
            'message' => 'User registered successfully! We have sent you a verification link to your email. Please check your inbox to continue. Thank you!',
            'redirect' => $this->redirectTo($user),
            'user' => $user,
            'verificationLink'=>$verificationLink
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
    if(!$request->email){

     return back()->with('error', 'Please provide a valid email address.');
    }


     $user = User::where('email', $request->email)->first();
    // 🔒 Check if user exists and is active
    if (!$user || $user->status == 0) {
        return back()->with('error', 'Account does not exist.');
    } 
   
    // ✅ Attempt to send the reset link
    $status = Password::sendResetLink($request->only('email'));

    if ($status === Password::RESET_LINK_SENT) {
        return back()->with('success', 'Password reset link has been sent to your email.');
    }

    return back()->with('error', 'Failed to send password reset link. Please try again later.');
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

        public function showVerifyEmailForm(Request $request, $encrypted)
        {
            try {
                $decrypted = Crypt::decryptString($encrypted);
                [$email, $expectedCode, $timestamp] = explode('/', $decrypted);

                $user = User::where('email', $email)->firstOrFail();
                session(['verifyEmail' => $user->email]);

                return view('modules.auth.verify_email', [
                    'encrypted' => $encrypted,
                ]);
            } catch (\Exception $e) {
                // Handle invalid token or user not found
                return redirect()->route('register')->withErrors(['error' => 'Invalid or expired verification link.']);
            }
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
                

            return redirect()->to('/onboarding/' . $encryptedData)
            // return redirect()->to('/plans/public/' . $encryptedData)
                 ->with('success', 'Your email has been verified!');
            } catch (\Exception $e) { 
                Log::error('Failed to verify email: ' . $e->getMessage());
                return back()->withErrors(['error' => 'Verification failed.'. $e->getMessage()]);
            }
        }

        
        public function viewPublicPlans(Request $request,$encrypted){
            
            // If encrypted parameter is provided, verify and update user status
            if($encrypted) {
                try {
                    $decrypted = Crypt::decryptString($encrypted);
                    [$email, $expectedCode, $timestamp] = explode('/', $decrypted);

                    $user = User::where('email', $email)->first();
                    if ($user && $user->status == 0) {
                        // Update user status to 1 (verified/active)
                        $user->status = 1;
                        $user->save();
                        
                        Log::info('User status updated to active via public plans access', [
                            'user_id' => $user->id,
                            'email' => $user->email
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to decrypt verification link in viewPublicPlans: ' . $e->getMessage());
                }
            }
         
            $getMostlyUsed = Plan::getMostlyUsed();
            $plans = Plan::with('features')->where('is_active', true)->where(function($query) {
                $query->where('is_discounted', 0)->orWhereNull('is_discounted');
            })->get();

            $publicPage=true;
            return view('customer.public_outside.plans', compact('plans', 'getMostlyUsed','publicPage','encrypted'));
        } 

        public function companyOnBoarding(Request $request,$encrypted){
          
           
            return view('modules.auth.company_onboarding',['publicPage'=>true,'encrypted'=>$encrypted]);
        } 



public function companyOnBoardingStore(Request $request)
{
            $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'role' => 'required|string',
            'company_name' => 'required|string|max:255',
            'website' => 'nullable|url|max:255',
            'company_size' => 'required|string',
            'inboxes_tested' => 'required|string',
            'monthly_spend' => 'required|string',
        ]);

        $data = [
            'user_id' => auth()->id() ?? 1, // Replace with dynamic user if available
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),
            'role' => $request->input('role'),
            'company_name' => $request->input('company_name'),
            'website' => $request->input('website'),
            'company_size' => $request->input('company_size'),
            'inboxes_tested' => $request->input('inboxes_tested'),
            'monthly_spend' => $request->input('monthly_spend'),
        ];

        $onboarding = Onboarding::create($data);

        if($onboarding){
        return response()->json([
            'status' => 'success',
            'message' => 'Onboarding data submitted successfully.',
            'data' => $onboarding
        ]);
      }
     else{
         return response()->json([
            'status' => 'false',
            'message' => 'Failed to save boarding details.'
            
        ]);
    }
   
}


public function resendVerificationEmail(Request $request)
{
    
    try {
        // Retrieve email from session of usr
        $email = session('verifyEmail');

        if (!$email) {
            return back()->withErrors(['error' => 'Verification email session not found.']);
        }
        

        // Find user by email
        $user = User::where('email', $email)->first();
        if (!$user) {
            return back()->withErrors(['email' => 'User not found.']);
        }
        // Generate new OTP
        $verificationCode = rand(1000, 9999);
        $user->email_verification_code = $verificationCode;
        $user->save();
        $payload = $user->email . '/' . $verificationCode . '/' . now()->timestamp;
        $newEncrypted = Crypt::encryptString($payload); 
    
         try {
        Mail::to($user->email)->queue(new EmailVerificationMail($user, ''));
        } catch (\Exception $e) {
            Log::error('Failed to send email verification code: '.$user->email.' '.$e->getMessage());
        }   
        return redirect()->to(url('/email_verification/' . $newEncrypted))
                         ->with('success', 'A new verification code has been sent to your email.');
                         
    } catch (\Exception $e) {
        \Log::error('Resend verification failed: ' . $e->getMessage());
        return back()->withErrors(['error' => 'Something went wrong while resending the verification code.']);
    }
}


}