<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\PromotionRun;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_promotion_run_prepares_all_active_students(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $years = AcademicYear::orderBy('starts_on')->get();
        $fromSection = Enrollment::firstOrFail()->section_id;
        $toSection = Section::where('id', '!=', $fromSection)->whereHas('gradeLevel', fn ($q) => $q->where('sequence', 13))->firstOrFail();
        $activeCount = Enrollment::where('section_id', $fromSection)->where('status', 'active')->count();

        $this->actingAs($principal)->post(route('promotions.create'), [
            'from_year_id' => $years[0]->id, 'to_year_id' => $years[1]->id,
            'from_section_id' => $fromSection, 'to_section_id' => $toSection->id,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseCount('promotion_decisions', $activeCount);
        $this->assertSame('draft', PromotionRun::first()->status);
    }

    public function test_completed_run_creates_next_year_enrollments(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $years = AcademicYear::orderBy('starts_on')->get();
        $from = Enrollment::firstOrFail()->section_id;
        $to = Section::where('id', '!=', $from)->whereHas('gradeLevel', fn ($q) => $q->where('sequence', 13))->firstOrFail();
        $this->actingAs($principal)->post(route('promotions.create'), ['from_year_id' => $years[0]->id, 'to_year_id' => $years[1]->id, 'from_section_id' => $from, 'to_section_id' => $to->id]);
        $run = PromotionRun::with('decisions')->firstOrFail();
        $decisions = $run->decisions->mapWithKeys(fn ($decision) => [$decision->id => 'promote'])->all();

        $this->actingAs($principal)->post(route('promotions.complete', $run), ['decision' => $decisions])->assertRedirect();

        $this->assertSame('completed', $run->fresh()->status);
        $this->assertSame($run->decisions->count(), Enrollment::where('academic_year_id', $years[1]->id)->where('status', 'active')->count());
        $this->assertSame(0, Enrollment::where('academic_year_id', $years[0]->id)->where('status', 'active')->count());
    }
}
