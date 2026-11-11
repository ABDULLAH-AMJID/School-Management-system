<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StudentManagementTest extends TestCase
{
    use RefreshDatabase;

    private function principal(): User
    {
        $school = School::create(['public_id' => (string) Str::ulid(), 'name' => 'Test School', 'code' => 'TEST']);
        $permission = Permission::create(['name' => 'students.create', 'module' => 'People', 'label' => 'Create']);
        $view = Permission::create(['name' => 'students.view', 'module' => 'People', 'label' => 'View']);
        $role = Role::create(['name' => 'principal', 'label' => 'Principal']);
        $role->permissions()->attach([$permission->id, $view->id]);
        $user = User::factory()->create(['school_id' => $school->id, 'public_id' => (string) Str::ulid()]);
        $user->roles()->attach($role);

        return $user;
    }

    public function test_authorized_user_can_admit_student(): void
    {
        $user = $this->principal();
        $this->actingAs($user)->post('/students', ['admission_no' => 'A-001', 'first_name' => 'Ayesha', 'last_name' => 'Khan', 'gender' => 'female', 'admitted_on' => '2026-11-01', 'status' => 'active'])->assertRedirect('/students');
        $this->assertDatabaseHas('students', ['school_id' => $user->school_id, 'admission_no' => 'A-001']);
    }

    public function test_duplicate_admission_number_is_rejected(): void
    {
        $user = $this->principal();
        $payload = ['admission_no' => 'A-001', 'first_name' => 'Ayesha', 'gender' => 'female', 'admitted_on' => '2026-11-01', 'status' => 'active'];
        $this->actingAs($user)->post('/students', $payload);
        $this->actingAs($user)->post('/students', $payload)->assertSessionHasErrors('admission_no');
    }
}
