<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuditMutations
{
    private const SENSITIVE = ['password', 'password_confirmation', 'current_password', 'token', 'secret', 'connector_token', '_token'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if ($request->user() && in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true) && $response->getStatusCode() < 400) {
            $data = collect($request->except(self::SENSITIVE))->map(fn ($value) => is_string($value) && mb_strlen($value) > 500 ? mb_substr($value, 0, 500).'…' : $value)->all();
            AuditLog::create(['school_id' => $request->user()->school_id, 'actor_id' => $request->user()->id, 'event' => strtolower($request->method()).':'.($request->route()?->getName() ?? $request->path()), 'auditable_type' => 'http_request', 'auditable_id' => (string) ($request->route('student')?->id ?? $request->route('invoice')?->id ?? $request->route('complaint')?->id ?? ''), 'old_values' => null, 'new_values' => $data, 'ip_address' => $request->ip(), 'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000), 'created_at' => now()]);
        }

return $response;
    }
}
