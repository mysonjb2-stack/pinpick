<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\VisitLog;

class TrackVisit
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($request->isMethod('GET') && !$request->ajax() && !$request->is('api/*', '_*', 'admin/*')) {
            try {
                VisitLog::create([
                    'user_id' => $request->user()?->id,
                    'ip' => $request->ip(),
                    'path' => substr($request->path(), 0, 500),
                    'user_agent' => substr($request->userAgent() ?? '', 0, 500),
                    'visited_date' => now()->toDateString(),
                ]);
            } catch (\Throwable $e) {
            }
        }

        return $response;
    }
}
