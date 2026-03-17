<?php

namespace App\Http\Controllers\Organiser;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $organiser = $request->attributes->get('organiser');
        return view('organiser.profile.show', compact('organiser'));
    }

    public function edit(Request $request)
    {
        $organiser = $request->attributes->get('organiser');
        return view('organiser.profile.edit', compact('organiser'));
    }

    public function update(Request $request)
    {
        $organiser = $request->attributes->get('organiser');

        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'email'        => 'required|email|unique:organisers,email,' . $organiser->id,
            'phone'        => 'nullable|string|max:30',
            'website'      => 'nullable|url|max:255',
            'bio'          => 'nullable|string|max:2000',
            'logo'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo
            if ($organiser->logo) {
                Storage::disk('public')->delete($organiser->logo);
            }
            $validated['logo'] = $request->file('logo')->store('organisers/logos', 'public');
        }

        $organiser->update($validated);

        Log::info('[Organiser] Profile updated: ' . $organiser->email);

        return redirect()->route('organiser.profile.show')
            ->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $organiser = $request->attributes->get('organiser');

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($validated['current_password'], $organiser->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $organiser->update(['password' => Hash::make($validated['password'])]);

        Log::info('[Organiser] Password changed: ' . $organiser->email);

        return back()->with('success', 'Password updated successfully.');
    }

    public function destroy(Request $request)
    {
        $organiser = $request->attributes->get('organiser');

        // Cannot delete if has active events
        if ($organiser->hasActiveEvents()) {
            return back()->withErrors([
                'delete' => 'You cannot delete your account while you have active upcoming events. Please cancel or complete all events first.'
            ]);
        }

        $request->validate(['confirm_delete' => 'required|in:DELETE']);

        Log::info('[Organiser] Account deleted: ' . $organiser->email);

        if ($organiser->logo) {
            Storage::disk('public')->delete($organiser->logo);
        }

        $organiser->delete();
        $request->session()->flush();

        return redirect()->route('home')->with('info', 'Your organiser account has been permanently deleted.');
    }
}