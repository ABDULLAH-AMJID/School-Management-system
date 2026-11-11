<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_principal_can_update_school_identity_and_defaults(): void
    {
        $this->seed();
        $user = User::where('email', 'principal@school.test')->firstOrFail();
        $this->actingAs($user)->put(route('settings.school.update'), ['name' => 'Sahiwal Scholars School', 'code' => 'SSS', 'board' => 'BISE Sahiwal', 'phone' => '+923001112233', 'email' => 'office@sss.edu.pk', 'address' => 'Sahiwal, Punjab', 'timezone' => 'Asia/Karachi', 'currency' => 'PKR', 'principal_name' => 'Abdullah Amjid', 'registration_no' => 'BISE-123', 'website' => 'https://sss.edu.pk', 'attendance_start_time' => '07:45', 'late_after_minutes' => 10, 'receipt_prefix' => 'RCP', 'voucher_prefix' => 'EXP'])->assertRedirect()->assertSessionHas('success');
        $school = $user->school->refresh();
        $this->assertSame('Sahiwal Scholars School', $school->name);
        $this->assertSame('07:45', $school->settings['attendance_start_time']);
        $this->assertSame(10, $school->settings['late_after_minutes']);
    }

    public function test_invalid_phone_and_timezone_are_rejected(): void
    {
        $this->seed();
        $user = User::where('email', 'principal@school.test')->firstOrFail();
        $this->actingAs($user)->put(route('settings.school.update'), ['name' => 'School', 'code' => 'SCH', 'board' => 'Punjab', 'phone' => '03001234567', 'timezone' => 'Mars/Base', 'currency' => 'PKR', 'attendance_start_time' => '08:00', 'late_after_minutes' => 0, 'receipt_prefix' => 'RCP', 'voucher_prefix' => 'EV'])->assertSessionHasErrors(['phone', 'timezone']);
    }
}
