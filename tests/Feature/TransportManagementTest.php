<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\StudentTransportAssignment;
use App\Models\TransportRoute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransportManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_be_assigned_to_valid_route_stop(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $student = Student::firstOrFail();
        $route = TransportRoute::with('stops')->firstOrFail();

        $this->actingAs($principal)->post(route('transport.assignments.store'), [
            'student_id' => $student->id, 'route_id' => $route->id,
            'stop_id' => $route->stops->first()->id, 'starts_on' => today()->toDateString(),
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('student_transport_assignments', ['student_id' => $student->id, 'status' => 'active']);
    }

    public function test_vehicle_capacity_cannot_be_exceeded(): void
    {
        $this->seed();
        $principal = User::where('email', 'principal@school.test')->firstOrFail();
        $route = TransportRoute::with(['stops', 'vehicle'])->firstOrFail();
        $route->vehicle->update(['capacity' => 1]);
        $students = Student::take(2)->get();
        StudentTransportAssignment::create(['student_id' => $students[0]->id, 'academic_year_id' => 1, 'transport_route_id' => $route->id, 'transport_stop_id' => $route->stops->first()->id, 'starts_on' => today(), 'status' => 'active']);

        $this->actingAs($principal)->post(route('transport.assignments.store'), [
            'student_id' => $students[1]->id, 'route_id' => $route->id,
            'stop_id' => $route->stops->first()->id, 'starts_on' => today()->toDateString(),
        ])->assertSessionHasErrors('route_id');

        $this->assertDatabaseCount('student_transport_assignments', 1);
    }
}
