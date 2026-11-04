<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PolicyController extends Controller
{
    public function show(Request $request): View
    {
        return view('policy.show', ['user' => $request->user()]);
    }

    public function accept(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $request->validate(['agree' => ['accepted']], ['agree.accepted' => 'Anda harus menyetujui kebijakan untuk melanjutkan.']);

        $user = $request->user();
        $user->forceFill([
            'policy_accepted_at' => now(),
            'policy_version' => config('booking.policy_version'),
        ])->save();

        $logger->log('ACCEPT_POLICY', $user, 'Menyetujui kebijakan versi '.config('booking.policy_version'));

        return redirect()->intended(route('dashboard'));
    }
}
