<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Account;
use App\Models\AssessmentComponent;
use App\Models\CertificateTemplate;
use App\Models\Enrollment;
use App\Models\ExamSession;
use App\Models\FeeInvoice;
use App\Models\FixedAsset;
use App\Models\GradeLevel;
use App\Models\InventoryItem;
use App\Models\LeaveRequest;
use App\Models\LibraryBook;
use App\Models\LibraryCopy;
use App\Models\MessageTemplate;
use App\Models\Notice;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Room;
use App\Models\School;
use App\Models\SchoolEvent;
use App\Models\Section;
use App\Models\StaffProfile;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\Term;
use App\Models\TimetableEntry;
use App\Models\TimetablePeriod;
use App\Models\TransportRoute;
use App\Models\TransportStop;
use App\Models\User;
use App\Models\Vehicle;
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
            ['exams.manage', 'Assessment', 'Manage examinations'], ['exams.publish', 'Assessment', 'Publish results'],
            ['messages.manage', 'Communication', 'Manage messages'], ['messages.approve', 'Communication', 'Approve messages'],
            ['hr.manage', 'HR', 'Manage staff'], ['hr.approve', 'HR', 'Approve leave'], ['payroll.manage', 'Payroll', 'Generate payroll'], ['payroll.approve', 'Payroll', 'Approve payroll'],
            ['timetable.manage', 'Academics', 'Manage timetable'],
            ['library.manage', 'Library', 'Manage catalogue'], ['library.issue', 'Library', 'Issue and return books'],
            ['transport.manage', 'Transport', 'Manage school transport'],
            ['inventory.manage', 'Inventory', 'Manage stock and assets'], ['inventory.issue', 'Inventory', 'Issue and receive stock'],
            ['engagement.manage', 'Engagement', 'Manage notices and events'], ['complaints.manage', 'Engagement', 'Register complaints'], ['complaints.resolve', 'Engagement', 'Resolve complaints'],
            ['reports.view', 'Reporting', 'View reports'], ['reports.export', 'Reporting', 'Export reports'],
            ['security.view', 'Security', 'View audit and security'], ['backups.create', 'Security', 'Create encrypted backups'],
            ['certificates.issue', 'Documents', 'Issue certificates'], ['certificates.revoke', 'Documents', 'Revoke certificates'],
            ['promotions.manage', 'Academics', 'Prepare promotions'], ['promotions.complete', 'Academics', 'Complete promotions'],
        ])->map(fn ($p) => Permission::create(['name' => $p[0], 'module' => $p[1], 'label' => $p[2]]));
        $principal = Role::create(['name' => 'principal', 'label' => 'Principal', 'system' => true]);
        $principal->permissions()->sync($permissions->pluck('id'));
        $user = User::create(['school_id' => $school->id, 'public_id' => (string) Str::ulid(), 'name' => 'Abdullah Amjid', 'email' => 'principal@school.test', 'phone' => '+923001234567', 'status' => 'active', 'password' => 'password']);
        $user->roles()->attach($principal);
        $teacherUser = User::create(['school_id' => $school->id, 'public_id' => (string) Str::ulid(), 'name' => 'Sarah Ahmed', 'email' => 'teacher@school.test', 'phone' => '+923111234567', 'status' => 'active', 'password' => 'password']);
        $staff = StaffProfile::create(['school_id' => $school->id, 'user_id' => $teacherUser->id, 'employee_no' => 'EMP-0001', 'designation' => 'Senior Teacher', 'department' => 'Academics', 'joined_on' => '2023-08-01', 'employment_type' => 'permanent', 'basic_salary' => 8500000, 'status' => 'active']);
        LeaveRequest::create(['staff_profile_id' => $staff->id, 'type' => 'casual', 'starts_on' => today()->addDays(3), 'ends_on' => today()->addDays(4), 'days' => 2, 'reason' => 'Family commitment', 'status' => 'pending']);
        foreach ([['1000', 'Cash on Hand', 'asset'], ['1010', 'Bank Account', 'asset'], ['1100', 'Student Fee Receivable', 'asset'], ['4000', 'Tuition Fee Income', 'income'], ['5000', 'Salary Expense', 'expense']] as $account) {
            Account::create(['school_id' => $school->id, 'code' => $account[0], 'name' => $account[1], 'type' => $account[2]]);
        }

        InventoryItem::create(['school_id' => $school->id, 'sku' => 'STA-001', 'name' => 'A4 Paper Ream', 'category' => 'Stationery', 'unit' => 'ream', 'quantity' => 48, 'reorder_level' => 15, 'average_cost' => 145000, 'location' => 'Main Store', 'active' => true]);
        FixedAsset::create(['school_id' => $school->id, 'asset_tag' => 'IT-0001', 'name' => 'Administration Laptop', 'category' => 'IT Equipment', 'purchased_on' => '2026-04-10', 'purchase_cost' => 18500000, 'location' => 'Principal Office', 'assigned_to' => 'Principal', 'condition' => 'good', 'status' => 'in_use']);

        $demoBook = LibraryBook::create(['school_id' => $school->id, 'isbn' => '9780199062131', 'title' => 'Mathematics for Secondary Schools', 'author' => 'Punjab Textbook Board', 'publisher' => 'PCTB', 'category' => 'Mathematics', 'publication_year' => 2026, 'shelf' => 'M-01']);
        LibraryCopy::create(['library_book_id' => $demoBook->id, 'accession_no' => 'LIB-00001', 'barcode' => '9780199062131-001', 'condition' => 'good', 'status' => 'available', 'acquired_on' => today(), 'cost' => 95000]);

        Notice::create(['school_id' => $school->id, 'title' => 'Mid-Term Examination Schedule', 'body' => 'The mid-term examinations will begin on 16 November. Students should follow the published timetable.', 'audience' => 'all', 'starts_on' => today(), 'ends_on' => today()->addDays(14), 'pinned' => true, 'status' => 'published', 'created_by' => $user->id]);
        SchoolEvent::create(['school_id' => $school->id, 'title' => 'Parent–Teacher Meeting', 'description' => 'Mid-term progress discussion.', 'starts_at' => today()->addDays(10)->setTime(9, 0), 'ends_at' => today()->addDays(10)->setTime(13, 0), 'type' => 'meeting', 'audience' => 'parents', 'location' => 'Main Campus', 'created_by' => $user->id]);

        CertificateTemplate::create(['school_id' => $school->id, 'name' => 'Bonafide Student Certificate', 'type' => 'bonafide', 'title' => 'BONAFIDE CERTIFICATE', 'body' => 'This is to certify that {{student_name}}, admission number {{admission_no}}, is a bonafide student of {{school_name}}. This certificate is issued on {{date}} upon request.', 'active' => true]);
        CertificateTemplate::create(['school_id' => $school->id, 'name' => 'Character Certificate', 'type' => 'character', 'title' => 'CHARACTER CERTIFICATE', 'body' => 'This is to certify that {{student_name}} studied at {{school_name}} under admission number {{admission_no}}. During this period, the student maintained good conduct and character.', 'active' => true]);

        MessageTemplate::create(['school_id' => $school->id, 'event' => 'absence', 'name' => 'Student absence', 'body' => 'Dear Parent, {{student_name}} ({{admission_no}}) was marked absent today. Please contact the school if needed.', 'mode' => 'approval', 'quiet_from' => '20:00', 'quiet_until' => '07:00', 'active' => true]);
        MessageTemplate::create(['school_id' => $school->id, 'event' => 'fee_due', 'name' => 'Fee reminder', 'body' => 'Dear Parent, fee payment for {{student_name}} is due. Outstanding amount: PKR {{amount}}. Please ignore if already paid.', 'mode' => 'approval', 'quiet_from' => '20:00', 'quiet_until' => '07:00', 'active' => true]);

        $year = AcademicYear::create(['school_id' => $school->id, 'name' => '2026–27', 'starts_on' => '2026-04-01', 'ends_on' => '2027-03-31', 'status' => 'active', 'is_current' => true]);
        AcademicYear::create(['school_id' => $school->id, 'name' => '2027–28', 'starts_on' => '2027-04-01', 'ends_on' => '2028-03-31', 'status' => 'draft', 'is_current' => false]);
        Term::create(['academic_year_id' => $year->id, 'name' => 'Term 1', 'starts_on' => '2026-04-01', 'ends_on' => '2026-08-31', 'sequence' => 1]);
        Term::create(['academic_year_id' => $year->id, 'name' => 'Term 2', 'starts_on' => '2026-09-01', 'ends_on' => '2027-03-31', 'sequence' => 2]);
        $gradeNames = ['Playgroup', 'Nursery', 'Prep', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Intermediate I', 'Intermediate II'];
        $sections = collect($gradeNames)->map(function ($name, $index) use ($school) {
            $grade = GradeLevel::create(['school_id' => $school->id, 'name' => $name, 'code' => 'G'.($index + 1), 'sequence' => $index + 1, 'board_level' => $index >= 11 ? 'Punjab Board' : null]);

            return Section::create(['grade_level_id' => $grade->id, 'name' => 'A', 'capacity' => 40]);
        });
        $demoSection = $sections[11];

        $vehicle = Vehicle::create(['school_id' => $school->id, 'registration_no' => 'SLW-2026-01', 'make_model' => 'Toyota Coaster', 'capacity' => 30, 'driver_name' => 'Muhammad Aslam', 'driver_phone' => '+923221234567', 'status' => 'active', 'fitness_expires_on' => '2027-06-30']);
        $route = TransportRoute::create(['school_id' => $school->id, 'vehicle_id' => $vehicle->id, 'name' => 'Farid Town Route', 'code' => 'RT-01', 'departure_time' => '07:00', 'return_time' => '14:15', 'status' => 'active']);
        TransportStop::create(['transport_route_id' => $route->id, 'name' => 'Farid Town Gate', 'sequence' => 1, 'pickup_time' => '07:15', 'dropoff_time' => '14:35', 'monthly_fee' => 350000]);

        $room = Room::create(['school_id' => $school->id, 'name' => 'Room 9-A', 'building' => 'Main Block', 'capacity' => 40, 'type' => 'classroom']);
        $periods = collect([['Period 1', '08:00', '08:45'], ['Period 2', '08:45', '09:30'], ['Period 3', '09:30', '10:15'], ['Break', '10:15', '10:35'], ['Period 4', '10:35', '11:20'], ['Period 5', '11:20', '12:05']])->map(fn ($p, $i) => TimetablePeriod::create(['school_id' => $school->id, 'name' => $p[0], 'starts_at' => $p[1], 'ends_at' => $p[2], 'sequence' => $i + 1, 'is_break' => $p[0] === 'Break']));

        $math = Subject::create(['school_id' => $school->id, 'name' => 'Mathematics', 'code' => 'MATH-09', 'type' => 'theory', 'max_marks' => 100]);
        $english = Subject::create(['school_id' => $school->id, 'name' => 'English', 'code' => 'ENG-09', 'type' => 'theory', 'max_marks' => 100]);
        $exam = ExamSession::create(['school_id' => $school->id, 'academic_year_id' => $year->id, 'term_id' => $year->terms()->first()->id, 'name' => 'Mid-Term Examination', 'type' => 'internal', 'starts_on' => '2026-11-16', 'ends_on' => '2026-11-25', 'status' => 'marks_entry']);
        AssessmentComponent::create(['exam_session_id' => $exam->id, 'subject_id' => $math->id, 'grade_level_id' => $demoSection->grade_level_id, 'name' => 'Written Paper', 'max_marks' => 100, 'weight_percent' => 100, 'pass_marks' => 33]);
        AssessmentComponent::create(['exam_session_id' => $exam->id, 'subject_id' => $english->id, 'grade_level_id' => $demoSection->grade_level_id, 'name' => 'Written Paper', 'max_marks' => 100, 'weight_percent' => 100, 'pass_marks' => 33]);
        $mathAssignment = TeachingAssignment::create(['academic_year_id' => $year->id, 'section_id' => $demoSection->id, 'subject_id' => $math->id, 'teacher_id' => $teacherUser->id, 'class_teacher' => true]);
        TimetableEntry::create(['academic_year_id' => $year->id, 'weekday' => 1, 'period_id' => $periods[0]->id, 'section_id' => $demoSection->id, 'teaching_assignment_id' => $mathAssignment->id, 'room_id' => $room->id]);

        foreach (range(1, 8) as $i) {
            $student = Student::create(['school_id' => $school->id, 'public_id' => (string) Str::ulid(), 'admission_no' => 'GF-26-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT), 'first_name' => ['Ayaan', 'Fatima', 'Muhammad', 'Ayesha', 'Ali', 'Zainab', 'Hamza', 'Hira'][$i - 1], 'last_name' => ['Khan', 'Ahmed', 'Raza', 'Malik', 'Hussain', 'Iqbal', 'Shah', 'Noor'][$i - 1], 'date_of_birth' => now()->subYears(8 + $i), 'gender' => $i % 2 ? 'male' : 'female', 'status' => 'active', 'admitted_on' => now()->subMonths($i)]);
            Enrollment::create(['student_id' => $student->id, 'academic_year_id' => $year->id, 'section_id' => $demoSection->id, 'roll_no' => str_pad((string) $i, 2, '0', STR_PAD_LEFT), 'status' => 'active']);
            FeeInvoice::create(['school_id' => $school->id, 'student_id' => $student->id, 'number' => 'INV-2026-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT), 'issue_date' => today()->startOfMonth(), 'due_date' => today()->startOfMonth()->addDays(10), 'subtotal' => 1250000, 'discount' => $i === 2 ? 125000 : 0, 'fine' => 0, 'total' => $i === 2 ? 1125000 : 1250000, 'paid' => 0, 'status' => 'issued']);
        }
    }
}
