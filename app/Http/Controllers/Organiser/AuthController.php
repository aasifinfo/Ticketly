<?php

namespace App\Http\Controllers\Organiser;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Organiser;
use App\Models\SystemSetting;
use App\Models\EmailLog;
use App\Support\AdminAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\AdminNewOrganiser;

class AuthController extends Controller
{
    private const SESSION_KEY     = 'organiser_id';
    private const INACTIVITY_MINS = 60; // Session expires after 60 minutes of inactivity

    // ── Register ──────────────────────────────────────────────────
    public function showRegister()
    {
        if (session(self::SESSION_KEY)) return redirect()->route('organiser.dashboard');
        return view('organiser.auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email'        => 'required|email|unique:organisers,email',
            'password'     => 'required|string|min:8|confirmed',
            'phone'        => 'required|string|max:30',
        ]);

        $organiser = Organiser::create([
            'name'         => $validated['name'],
            'company_name' => $validated['company_name'],
            'email'        => $validated['email'],
            'password'     => Hash::make($validated['password']),
            'phone'        => $validated['phone'] ?? null,
            'is_approved'  => false,
        ]);

        Log::info('[Organiser] New registration: ' . $organiser->email);

        try {
            Mail::send('emails.organiser-registration-pending', [
                'organiser' => $organiser,
            ], function ($message) use ($organiser) {
                $message->to($organiser->email)
                    ->subject('Your Ticketly organiser registration is pending approval');
            });
            EmailLog::logSent($organiser->email, 'Organiser registration pending', 'organiser_registration_pending', $organiser);
        } catch (\Exception $e) {
            Log::error('[Organiser] Registration confirmation email failed: ' . $e->getMessage(), [
                'organiser_id' => $organiser->id,
                'email'        => $organiser->email,
            ]);
            EmailLog::logFailed($organiser->email, 'Organiser registration pending', $e->getMessage(), 'organiser_registration_pending', $organiser);
        }

        $adminEmail = null;
        try {
            $adminEmail = SystemSetting::getValue('admin_email', config('ticketly.support_email', 'support@ticketly.com'));
            if (!empty($adminEmail)) {
                Mail::to($adminEmail)->send(new AdminNewOrganiser($organiser));
                EmailLog::logSent($adminEmail, 'New organiser registration', 'admin_new_organiser', $organiser);
            }
        } catch (\Exception $e) {
            Log::error('[Organiser] Admin notification email failed: ' . $e->getMessage(), [
                'organiser_id' => $organiser->id,
            ]);
            if (!empty($adminEmail)) {
                EmailLog::logFailed($adminEmail, 'New organiser registration', $e->getMessage(), 'admin_new_organiser', $organiser);
            }
        }

        return view('organiser.auth.pending');
    }

    // ── Login ─────────────────────────────────────────────────────
    public function showLogin()
    {
        if (AdminAuth::user()) return redirect()->route('admin.dashboard');
        if ($this->getAuthenticatedOrganiser()) return redirect()->route('organiser.dashboard');
        return view('organiser.auth.login');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $admin = Admin::where('email', $validated['email'])->first();
        if ($admin && Hash::check($validated['password'], $admin->password)) {
            $request->session()->forget([self::SESSION_KEY, 'organiser_last_active']);
            AdminAuth::login($admin);
            return redirect()->route('admin.dashboard');
        }

        $organiser = Organiser::where('email', $validated['email'])->first();

        if (!$organiser || !Hash::check($validated['password'], $organiser->password)) {
            return back()->withErrors(['email' => 'Invalid email or password.'])->withInput();
        }

        if ($organiser->isSuspended()) {
            return back()->withErrors(['email' => 'This organiser account is suspended. Please contact support.'])->withInput();
        }

        if ($organiser->rejected_at) {
            return back()->withErrors(['email' => 'Your organiser account was rejected. Please contact support.'])->withInput();
        }

        if (!$organiser->isApproved()) {
            return redirect()->route('organiser.pending');
        }

        session([
            self::SESSION_KEY              => $organiser->id,
            'organiser_last_active'        => now()->timestamp,
        ]);

        $organiser->touchActivity();

        return redirect()->route('organiser.dashboard');
    }

    // ── Logout ────────────────────────────────────────────────────
    public function logout(Request $request)
    {
        $request->session()->forget([self::SESSION_KEY, 'organiser_last_active']);
        return redirect()->route('organiser.login')->with('info', 'You have been logged out.');
    }

    // ── Pending Approval ──────────────────────────────────────────
    public function pending()
    {
        return view('organiser.auth.pending');
    }

    // ── Forgot Password ───────────────────────────────────────────
    public function showForgotPassword()
    {
        return view('organiser.auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $organiser = Organiser::where('email', $request->email)->first();

        if ($organiser) {
            $token     = $organiser->generatePasswordResetToken();
            $resetLink = route('organiser.password.reset.form', ['token' => $token, 'email' => $organiser->email]);

            try {
                Mail::send('emails.organiser-password-reset', [
                    'organiser' => $organiser,
                    'resetLink' => $resetLink,
                ], function ($message) use ($organiser) {
                    $message->to($organiser->email)
                        ->subject('🔐 Reset Your Ticketly Organiser Password');
                });
                EmailLog::logSent($organiser->email, 'Organiser password reset', 'organiser_password_reset', $organiser);
            } catch (\Exception $e) {
                Log::error('[Organiser] Password reset email failed: ' . $e->getMessage());
                EmailLog::logFailed($organiser->email, 'Organiser password reset', $e->getMessage(), 'organiser_password_reset', $organiser);
                return back()->with('error', 'Email failed to send. Check logs.');
            }
        }

        // Always show success to prevent email enumeration
        return back()->with('success', 'If an account exists with that email, a reset link has been sent. It expires in 24 hours.');
    }

    public function showResetForm(Request $request)
    {
        return view('organiser.auth.reset-password', [
            'token' => $request->token,
            'email' => $request->email,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token'    => 'required|string',
            'email'    => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $organiser = Organiser::where('email', $validated['email'])->first();

        if (!$organiser || !$organiser->isResetTokenValid($validated['token'])) {
            return back()->withErrors(['token' => 'This password reset link is invalid or has expired.']);
        }

        $organiser->update(['password' => Hash::make($validated['password'])]);
        $organiser->clearResetToken();

        return redirect()->route('organiser.login')->with('success', 'Password reset successfully. You can now log in.');
    }

    // ── Auth Helper ───────────────────────────────────────────────
    public static function getAuthenticatedOrganiser(): ?Organiser
    {
        $id           = session(self::SESSION_KEY);
        $lastActive   = session('organiser_last_active');

        if (!$id) return null;

        // Inactivity check
        if ($lastActive && (now()->timestamp - $lastActive) > (self::INACTIVITY_MINS * 60)) {
            session()->forget([self::SESSION_KEY, 'organiser_last_active']);
            return null;
        }

        $organiser = Organiser::find($id);
        if (!$organiser || !$organiser->isApproved() || $organiser->isSuspended()) {
            session()->forget([self::SESSION_KEY, 'organiser_last_active']);
            return null;
        }

        // Refresh activity timestamp
        session(['organiser_last_active' => now()->timestamp]);
        $organiser->touchActivity();

        return $organiser;
    }
}
