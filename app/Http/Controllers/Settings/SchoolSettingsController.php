<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

final class SchoolSettingsController extends Controller
{
    public function edit(Request $request): View
    {
        return view('settings.school', ['school' => $request->user()->school]);
    }

    public function update(Request $request): RedirectResponse
    {
        $school = $request->user()->school;
        $data = $request->validate(['name' => ['required', 'string', 'max:150'], 'code' => ['required', 'alpha_dash', 'max:30', Rule::unique('schools', 'code')->ignore($school)], 'board' => ['required', 'string', 'max:100'], 'phone' => ['nullable', 'regex:/^\+[1-9]\d{7,14}$/'], 'email' => ['nullable', 'email', 'max:150'], 'address' => ['nullable', 'string', 'max:1000'], 'timezone' => ['required', Rule::in(timezone_identifiers_list())], 'currency' => ['required', Rule::in(['PKR'])], 'principal_name' => ['nullable', 'string', 'max:150'], 'registration_no' => ['nullable', 'string', 'max:100'], 'website' => ['nullable', 'url', 'max:255'], 'attendance_start_time' => ['required', 'date_format:H:i'], 'late_after_minutes' => ['required', 'integer', 'min:0', 'max:180'], 'receipt_prefix' => ['required', 'alpha_dash', 'max:12'], 'voucher_prefix' => ['required', 'alpha_dash', 'max:12'], 'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048'], 'remove_logo' => ['nullable', 'boolean']]);
        $settings = array_merge($school->settings ?? [], collect($data)->only(['principal_name', 'registration_no', 'website', 'attendance_start_time', 'late_after_minutes', 'receipt_prefix', 'voucher_prefix'])->all());
        $oldLogo = data_get($school->settings, 'logo_path');
        if ($request->hasFile('logo')) {
            $newLogo = $request->file('logo')->store('school-branding/'.$school->id, 'local');
            if ($newLogo === false) {
                return back()->withErrors(['logo' => 'Could not save the school logo.']);
            }
            $settings['logo_path'] = $newLogo;
        } elseif ($request->boolean('remove_logo')) {
            unset($settings['logo_path']);
        }
        $school->update([...collect($data)->except([...array_keys($settings), 'logo', 'remove_logo'])->all(), 'settings' => $settings]);
        if (($request->hasFile('logo') || $request->boolean('remove_logo')) && is_string($oldLogo) && str_starts_with($oldLogo, 'school-branding/'.$school->id.'/')) {
            Storage::disk('local')->delete($oldLogo);
        }

        return back()->with('success', 'School settings updated.');
    }
}
