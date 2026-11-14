<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
    public function test_school_logo_appears_on_login_and_invalid_files_are_rejected(): void
    {
        Storage::fake('local');
        $this->seed();
        $user = User::where('email', 'principal@school.test')->firstOrFail();
        $fields = ['name' => 'Sahiwal School', 'code' => 'SCH', 'board' => 'Punjab', 'timezone' => 'Asia/Karachi', 'currency' => 'PKR', 'attendance_start_time' => '08:00', 'late_after_minutes' => 0, 'receipt_prefix' => 'RCP', 'voucher_prefix' => 'EV'];
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+yBHsAAAAASUVORK5CYII=');
        $this->actingAs($user)->put(route('settings.school.update'), $fields + ['logo' => UploadedFile::fake()->createWithContent('logo.png', $png)])->assertRedirect()->assertSessionHasNoErrors();
        $path = $user->school->refresh()->settings['logo_path'];
        Storage::disk('local')->assertExists($path);
        auth()->logout();
        $this->get('/login')->assertOk()->assertSee('Sahiwal School')->assertSee(route('school.logo'));
        $this->get(route('school.logo'))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->actingAs($user)->put(route('settings.school.update'), $fields + ['logo' => UploadedFile::fake()->createWithContent('logo.svg', '<svg></svg>')])->assertSessionHasErrors('logo');
        $this->assertSame($path, $user->school->refresh()->settings['logo_path']);
        $this->actingAs($user)->put(route('settings.school.update'), $fields + ['remove_logo' => 1])->assertRedirect()->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($path);
        $this->get(route('school.logo'))->assertNotFound();
    }

    public function test_guest_cannot_change_branding(): void
    {
        $this->seed();
        $this->put(route('settings.school.update'), [])->assertRedirect(route('login'));
    }

}
