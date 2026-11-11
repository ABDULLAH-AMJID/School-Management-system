<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EngagementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_principal_can_publish_targeted_notice(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();

        $this->actingAs($principal)->post(route('engagement.notices.store'), [
            'title' => 'School closed', 'body' => 'School is closed due to maintenance.',
            'audience' => 'parents', 'starts_on' => today()->toDateString(),
            'ends_on' => today()->addDay()->toDateString(), 'pinned' => 1,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('notices', ['title' => 'School closed', 'audience' => 'parents', 'pinned' => true]);
    }

    public function test_complaint_can_be_registered_and_resolved(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $this->actingAs($principal)->post(route('engagement.complaints.store'), [
            'source' => 'parent', 'complainant_name' => 'Parent One', 'category' => 'Transport',
            'priority' => 'high', 'subject' => 'Bus delay', 'description' => 'Bus arrived late repeatedly.',
            'assigned_to' => $principal->id,
        ])->assertRedirect();
        $complaint = Complaint::firstOrFail();

        $this->actingAs($principal)->post(route('engagement.complaints.resolve', $complaint), [
            'resolution' => 'Route timing corrected and driver notified.',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame('resolved', $complaint->fresh()->status);
        $this->assertSame($principal->id, $complaint->fresh()->resolved_by);
        $this->assertNotNull($complaint->fresh()->resolved_at);
    }
}
