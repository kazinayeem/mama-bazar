<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthWebController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('phone', $credentials['phone'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'phone' => ['Invalid phone number or password.'],
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'phone' => ['Your account has been deactivated.'],
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        if (in_array($user->role, ['admin', 'manager', 'editor', 'staff']) || $user->custom_role) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return redirect()->intended(route('home'));
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:20|unique:users,phone',
            'email' => 'nullable|email|max:100',
            'password' => 'required|string|min:6',
            'marketing_opt_in' => 'nullable|boolean',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => 'user',
            'status' => 'active',
            'marketing_opt_in' => $request->boolean('marketing_opt_in', true),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        // If user provided an email, generate OTP and dispatch email
        if (!empty($user->email)) {
            $otpRes = \App\Services\EmailOtpService::generateOtp($user->email, 'account_verification', $user->id);
            if ($otpRes['success']) {
                $rendered = \App\Services\EmailTemplateService::render('account_verification_otp', [
                    'customer_name' => $user->name,
                    'customer_email' => $user->email,
                    'otp_code' => $otpRes['code'],
                    'otp_expires_minutes' => (string) $otpRes['expires_minutes'],
                ]);

                \App\Services\EmailDispatcherService::send(
                    $user->email,
                    $user->name,
                    $rendered['subject'],
                    $rendered['html'],
                    $rendered['plain'],
                    'otp'
                );

                session(['pending_verification_email' => $user->email]);

                return redirect()->route('auth.verify-otp')->with('success', 'Account created! Please enter the 6-digit verification code sent to your email.');
            }
        }

        return redirect()->route('home')->with('success', 'Account created successfully!');
    }

    public function showVerifyOtp(Request $request)
    {
        $email = session('pending_verification_email', Auth::user()?->email);
        if (empty($email)) {
            return redirect()->route('home');
        }

        return view('auth.verify-otp', compact('email'));
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|min:6|max:6',
        ]);

        $email = $request->input('email');
        $code = $request->input('code');

        $result = \App\Services\EmailOtpService::verifyOtp($email, $code, 'account_verification');

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        // Send welcome email if automated welcome emails are active
        if (\App\Services\EmailSettingService::isAutomationEnabled('welcome')) {
            $user = $result['user'] ?: Auth::user();
            $welcome = \App\Services\EmailTemplateService::render('welcome_email', [
                'customer_name' => $user?->name ?: 'Valued Customer',
                'customer_email' => $email,
            ]);

            \App\Services\EmailDispatcherService::send(
                $email,
                $user?->name,
                $welcome['subject'],
                $welcome['html'],
                $welcome['plain'],
                'transactional'
            );
        }

        session()->forget('pending_verification_email');

        return redirect()->route('home')->with('success', 'Email verified successfully! Welcome to Mama Bazar.');
    }

    public function resendOtp(Request $request)
    {
        $email = $request->input('email', session('pending_verification_email', Auth::user()?->email));

        if (empty($email)) {
            return back()->with('error', 'No email address found to resend verification code.');
        }

        $user = User::where('email', $email)->first();
        $otpRes = \App\Services\EmailOtpService::generateOtp($email, 'account_verification', $user?->id);

        if (!$otpRes['success']) {
            return back()->with('error', $otpRes['message']);
        }

        $rendered = \App\Services\EmailTemplateService::render('account_verification_otp', [
            'customer_name' => $user?->name ?: 'Customer',
            'customer_email' => $email,
            'otp_code' => $otpRes['code'],
            'otp_expires_minutes' => (string) $otpRes['expires_minutes'],
        ]);

        \App\Services\EmailDispatcherService::send(
            $email,
            $user?->name,
            $rendered['subject'],
            $rendered['html'],
            $rendered['plain'],
            'otp'
        );

        return back()->with('success', 'A new 6-digit verification code has been dispatched to your email.');
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendForgotPasswordOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $email = strtolower(trim($request->input('email')));

        $user = User::where('email', $email)->first();

        // Avoid revealing whether email is registered for security
        if ($user) {
            $otpRes = \App\Services\EmailOtpService::generateOtp($email, 'password_reset', $user->id);
            if ($otpRes['success']) {
                $rendered = \App\Services\EmailTemplateService::render('password_reset', [
                    'customer_name' => $user->name,
                    'customer_email' => $user->email,
                    'reset_url' => route('auth.reset-password', ['email' => $email]),
                ]);

                \App\Services\EmailDispatcherService::send(
                    $email,
                    $user->name,
                    $rendered['subject'],
                    $rendered['html'],
                    $rendered['plain'],
                    'auth'
                );
            }
        }

        session(['reset_email' => $email]);

        return redirect()->route('auth.reset-password', ['email' => $email])
            ->with('success', 'If an account exists with that email, a 6-digit reset code has been sent.');
    }

    public function showResetPassword(Request $request)
    {
        $email = $request->query('email', session('reset_email'));
        return view('auth.reset-password', compact('email'));
    }

    public function resetPasswordWithOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $email = strtolower(trim($request->input('email')));
        $code = $request->input('code');

        $result = \App\Services\EmailOtpService::verifyOtp($email, $code, 'password_reset');

        if (!$result['success']) {
            return back()->with('error', $result['message']);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return back()->with('error', 'User account not found.');
        }

        $user->update(['password' => Hash::make($request->input('password'))]);

        Auth::login($user);
        session()->forget('reset_email');

        return redirect()->route('home')->with('success', 'Your password has been successfully reset.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
