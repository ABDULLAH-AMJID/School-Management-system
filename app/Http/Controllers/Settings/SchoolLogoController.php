<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class SchoolLogoController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        // This deployment is single-school. Do not use this lookup for a multi-tenant deployment.
        $school = School::query()->orderBy('id')->firstOrFail();
        $path = data_get($school->settings, 'logo_path');
        abort_unless(is_string($path) && str_starts_with($path, 'school-branding/'.$school->id.'/') && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}
