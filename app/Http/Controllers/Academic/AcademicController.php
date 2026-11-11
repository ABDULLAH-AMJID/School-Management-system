<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AcademicController extends Controller
{
    public function index(Request $request): View
    {
        return view('academics.index', ['year' => AcademicYear::where('school_id', $request->user()->school_id)->where('is_current', true)->with('terms')->first(), 'grades' => GradeLevel::where('school_id', $request->user()->school_id)->with('sections')->orderBy('sequence')->get()]);
    }
}
