<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentTransportAssignment;
use App\Models\TransportRoute;
use App\Models\TransportStop;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class TransportController extends Controller
{
    public function index(Request $request): View
    {
        $schoolId = $request->user()->school_id;

        return view('transport.index', ['routes' => TransportRoute::where('school_id', $schoolId)->with(['vehicle', 'stops'])->withCount(['assignments' => fn ($q) => $q->where('status', 'active')])->get(), 'students' => Student::where(['school_id' => $schoolId, 'status' => 'active'])->orderBy('first_name')->get(), 'assignments' => StudentTransportAssignment::whereHas('route', fn ($q) => $q->where('school_id', $schoolId))->with(['student', 'route', 'stop'])->where('status', 'active')->get()]);
    }

    public function storeVehicle(Request $request): RedirectResponse
    {
        $data = $request->validate(['registration_no' => ['required', 'string', 'max:30'], 'make_model' => ['required', 'string', 'max:100'], 'capacity' => ['required', 'integer', 'between:1,100'], 'driver_name' => ['required', 'string', 'max:100'], 'driver_phone' => ['required', 'regex:/^\+[1-9]\d{7,14}$/']]);
        Vehicle::create($data + ['school_id' => $request->user()->school_id]);

        return back()->with('success', 'Vehicle added.');
    }

    public function assign(Request $request): RedirectResponse
    {
        $data = $request->validate(['student_id' => ['required', 'exists:students,id'], 'route_id' => ['required', 'exists:transport_routes,id'], 'stop_id' => ['required', 'exists:transport_stops,id'], 'starts_on' => ['required', 'date']]);
        $student = Student::findOrFail($data['student_id']);
        $route = TransportRoute::with('vehicle')->where('school_id', $request->user()->school_id)->findOrFail($data['route_id']);
        $stop = TransportStop::where('transport_route_id', $route->id)->findOrFail($data['stop_id']);
        abort_unless($student->school_id === $request->user()->school_id, 403);
        $year = AcademicYear::where(['school_id' => $request->user()->school_id, 'is_current' => true])->firstOrFail();
        $active = $route->assignments()->where('status', 'active')->count();
        if ($route->vehicle && $active >= $route->vehicle->capacity) {
            throw ValidationException::withMessages(['route_id' => 'Vehicle capacity has been reached.']);
        }StudentTransportAssignment::updateOrCreate(['student_id' => $student->id, 'academic_year_id' => $year->id], ['transport_route_id' => $route->id, 'transport_stop_id' => $stop->id, 'starts_on' => $data['starts_on'], 'status' => 'active']);

        return back()->with('success','Student assigned to transport route.');
    }
}
