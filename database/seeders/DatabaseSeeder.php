<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::create(['public_id' => (string) Str::ulid(), 'name' => 'Greenfield Higher Secondary School', 'code' => 'GFHSS', 'board' => 'Punjab Boards', 'timezone' => 'Asia/Karachi', 'currency' => 'PKR']);
        $permissions = collect([['students.view', 'People', 'View students'], ['students.create', 'People', 'Admit students'], ['students.update', 'People', 'Update students'], ['students.archive', 'People', 'Archive students'], ['attendance.manage', 'Attendance', 'Manage attendance'], ['finance.manage', 'Finance', 'Manage finance'], ['settings.manage', 'Platform', 'Manage settings']])->map(fn ($p) => Permission::create(['name' => $p[0], 'module' => $p[1], 'label' => $p[2]]));
        $principal = Role::create(['name' => 'principal', 'label' => 'Principal', 'system' => true]);
        $principal->permissions()->sync($permissions->pluck('id'));
        $user = User::create(['school_id' => $school->id, 'public_id' => (string) Str::ulid(), 'name' => 'Abdullah Amjid', 'email' => 'principal@school.test', 'phone' => '+923001234567', 'status' => 'active', 'password' => 'password']);
        $user->roles()->attach($principal);
        foreach (range(1, 8) as $i) {
            Student::create(['school_id' => $school->id, 'public_id' => (string) Str::ulid(), 'admission_no' => 'GF-26-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT), 'first_name' => ['Ayaan', 'Fatima', 'Muhammad', 'Ayesha', 'Ali', 'Zainab', 'Hamza', 'Hira'][$i - 1], 'last_name' => ['Khan', 'Ahmed', 'Raza', 'Malik', 'Hussain', 'Iqbal', 'Shah', 'Noor'][$i - 1], 'date_of_birth' => now()->subYears(8 + $i), 'gender' => $i % 2 ? 'male' : 'female', 'status' => 'active', 'admitted_on' => now()->subMonths($i)]);
        }
    }
}
