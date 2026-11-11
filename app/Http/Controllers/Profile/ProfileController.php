<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'phone' => ['nullable', 'regex:/^\+[1-9]\d{7,14}$/'], 'current_password' => ['required', 'current_password'], 'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()->symbols()]]);
        $user->update(['name' => $data['name'], 'phone' => $data['phone'] ?? null, 'password' => Hash::make($data['password']), 'must_change_password' => false]);
        $request->session()->regenerate();

        return back()->with('success', 'Profile and password updated securely.');
    }
}
