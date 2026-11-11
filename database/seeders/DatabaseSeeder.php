<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\Section;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::create(['public_id' => (string) Str::ulid(), 'name' => 'Greenfield Higher Secondary School', 'code' => 'GFHSS', 'board' => 'Punjab Boards', 'timezone' => 'Asia/Karachi', 'currency' => 'PKR']);
        $permissions = collect([
            ['students.view', 'People', 'View students'], ['students.create', 'People', 'Admit students'],
            ['students.update', 'People', 'Update students'], ['students.archive', 'People', 'Archive students'],
            ['attendance.manage', 'Attendance', 'Manage attendance'], ['finance.manage', 'Finance', 'Manage finance'],
            ['settings.manage', 'Platform', 'Manage settings'],
        ])->map(fn ($p) => Permission::create(['name' => $p[0], 'module' => $p[1], 'label' => $p[2]]));
        $principal = Role::create(['name' => 'principal', 'label' => 'Principal', 'system' => true]);
        $principal->permissions()->sync($permissions->pluck('id'));
        $user = User::create(['school_id' => $school->id, 'public_id' => (string) Str::ulid(), 'name' => 'Abdullah Amjid', 'email' => 'principal@school.test', 'phone' => '+923001234567', 'status' => 'active', 'password' => 'password']);
        $user->roles()->attach($principal);

        $year = AcademicYear::create(['school_id' => $school->id, 'name' => '2026–27', 'starts_on' => '2026-04-01', 'ends_on' => '2027-03-31', 'status' => 'active', 'is_current' => true]);
        Term::create(['academic_year_id' => $year->id, 'name' => 'Term 1', 'starts_on' => '2026-04-01', 'ends_on' => '2026-08-31', 'sequence' => 1]);
        Term::create(['academic_year_id' => $year->id, 'name' => 'Term 2', 'starts_on' => '2026-09-01', 'ends_on' => '2027-03-31', 'sequence' => 2]);
        $gradeNames = ['Playgroup', 'Nursery', 'Prep', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Intermediate I', 'Intermediate II'];
        $sections = collect($gradeNames)->map(function ($name, $index) use ($school) {
            $grade = GradeLevel::create(['school_id' => $school->id, 'name' => $name, 'code' => 'G'.($index + 1), 'sequence' => $index + 1, 'board_level' => $index >= 11 ? 'Punjab Board' : null]);

            return Section::create(['grade_level_id' => $grade->id, 'name' => 'A', 'capacity' => 40]);
        });
        $demoSection = $sections[11];
        foreach (range(1, 8) as $i) {
            $student = Student::create(['school_id' => $school->id, 'public_id' => (string) Str::ulid(), 'admission_no' => 'GF-26-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT), 'first_name' => ['Ayaan', 'Fatima', 'Muhammad', 'Ayesha', 'Ali', 'Zainab', 'Hamza', 'Hira'][$i - 1], 'last_name' => ['Khan', 'Ahmed', 'Raza', 'Malik', 'Hussain', 'Iqbal', 'Shah', 'Noor'][$i - 1], 'date_of_birth' => now()->subYears(8 + $i), 'gender' => $i % 2 ? 'male' : 'female', 'status' => 'active', 'admitted_on' => now()->subMonths($i)]);
            Enrollment::create(['student_id' => $student->id, 'academic_year_id' => $year->id, 'section_id' => $demoSection->id, 'roll_no' => str_pad((string) $i, 2, '0', STR_PAD_LEFT), 'status' => 'active']);
        }
    }
}
