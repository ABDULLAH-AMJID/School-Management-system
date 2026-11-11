<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_cannot_access_principal_access_control(): void
    {
        $this->seed();
        $teacher = User::where('email', 'teacher@school.test')->firstOrFail();

        $this->actingAs($teacher)->get(route('access.index'))->assertForbidden();
    }

    public function test_principal_can_create_custom_role_and_staff_account(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $permission = Permission::where('name', 'students.view')->firstOrFail();
        $this->actingAs($principal)->post(route('access.roles.store'), [
            'name' => 'receptionist', 'label' => 'Receptionist', 'permission_ids' => [$permission->id],
        ])->assertRedirect();
        $role = Role::where('name', 'receptionist')->firstOrFail();

        $this->actingAs($principal)->post(route('access.users.store'), [
            'name' => 'Front Desk', 'email' => 'reception@school.test', 'phone' => '+923331234567',
            'role_id' => $role->id, 'password' => 'SecurePass123', 'password_confirmation' => 'SecurePass123',
        ])->assertRedirect()->assertSessionHas('success');

        $user = User::where('email', 'reception@school.test')->firstOrFail();
        $this->assertTrue($user->roles->contains($role));
        $this->assertTrue($user->hasPermission('students.view'));
        $this->assertFalse($user->hasPermission('access.manage'));
    }

    public function test_suspended_account_is_logged_out_on_protected_route(): void
    {
        $this->seed();
        $teacher = User::where('email', 'teacher@school.test')->firstOrFail();
        $teacher->update(['status' => 'suspended']);

        $this->actingAs($teacher)->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
