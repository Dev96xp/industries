<?php

namespace App\Http\Middleware;

use App\Models\CompanySetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictByIp
{
    public function handle(Request $request, Closure $next): Response
    {
        $settings = CompanySetting::current();

        if (! $settings->ip_restriction_enabled) {
            return $next($request);
        }

        $allowed = collect(explode(',', $settings->allowed_ips ?? ''))
            ->map(fn ($ip) => trim($ip))
            ->filter()
            ->values();

        if ($allowed->isEmpty()) {
            return $next($request);
        }

        if ($request->user()?->hasRole('superadmin')) {
            return $next($request);
        }

        $clientIp = $request->ip();

        if ($allowed->contains($clientIp)) {
            return $next($request);
        }

        return response()->view('errors.ip-restricted', [
            'clientIp' => $clientIp,
        ], 403);
    }
}
