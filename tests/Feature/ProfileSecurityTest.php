<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_temporary_password_user_is_redirected_until_password_changed(): void
    {
        $this->seed();
        $teacher = User::where('email', 'teacher@school.test')->firstOrFail();
        $teacher->update(['must_change_password' => true]);
        $this->actingAs($teacher)->get('/attendance')->assertRedirect(route('profile.edit'));
        $this->actingAs($teacher)->get(route('profile.edit'))->assertOk();
        $this->actingAs($teacher)->put(route('profile.update'), ['name' => $teacher->name, 'phone' => $teacher->phone, 'current_password' => 'password', 'password' => 'Stronger#123', 'password_confirmation' => 'Stronger#123'])->assertRedirect()->assertSessionHas('success');
        $teacher->refresh();
        $this->assertFalse($teacher->must_change_password);
        $this->assertTrue(Hash::check('Stronger#123', $teacher->password));
        $this->actingAs($teacher)->get('/')->assertOk();
    }

    public function test_weak_password_and_wrong_current_password_are_rejected(): void
    {
        $this->seed();
        $u = User::where('email', 'principal@school.test')->firstOrFail();
        $this->actingAs($u)->put(route('profile.update'), ['name' => $u->name, 'current_password' => 'wrong', 'password' => 'weak', 'password_confirmation' => 'weak'])->assertSessionHasErrors(['current_password', 'password']);
    }
}
