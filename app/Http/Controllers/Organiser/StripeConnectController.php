<?php

namespace App\Http\Controllers\Organiser;

use App\Http\Controllers\Controller;
use App\Services\StripeConnectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StripeConnectController extends Controller
{
    public function __construct(private readonly StripeConnectService $stripeConnect)
    {}

    public function connect(Request $request)
    {
        $organiser = $request->attributes->get('organiser');

        try {
            $accountId = $this->stripeConnect->ensureExpressAccount($organiser);
            $url = $this->stripeConnect->createOnboardingLink(
                $accountId,
                route('organiser.stripe.refresh'),
                route('organiser.stripe.return')
            );

            return redirect()->away($url);
        } catch (\Exception $e) {
            Log::error('[Stripe Connect] Onboarding init failed: ' . $e->getMessage());
            return back()->withErrors(['stripe' => 'Unable to start Stripe onboarding right now. Please try again.']);
        }
    }

    public function refresh(Request $request)
    {
        return $this->connect($request);
    }

    public function return(Request $request)
    {
        $organiser = $request->attributes->get('organiser');

        try {
            $isComplete = $this->stripeConnect->syncOnboardingStatus($organiser);
            $message = $isComplete
                ? 'Stripe account connected successfully.'
                : 'Stripe onboarding is not complete yet. Please finish onboarding to receive payouts.';

            return redirect()->route('organiser.profile.show')->with('success', $message);
        } catch (\Exception $e) {
            Log::error('[Stripe Connect] Onboarding return failed: ' . $e->getMessage());
            return redirect()->route('organiser.profile.show')
                ->withErrors(['stripe' => 'Unable to verify Stripe onboarding status. Please try again.']);
        }
    }
}
