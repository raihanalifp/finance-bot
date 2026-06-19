<?php

namespace App\Http\Middleware;

use App\Http\Middleware\RequestId;
use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TrackActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);
        $response = $next($request);

        if ($this->shouldTrack($request)) {
            try {
                ActivityLog::query()->create([
                    'user_id' => Auth::id(),
                    'request_id' => (string) $request->attributes->get(RequestId::ATTRIBUTE),
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'route_name' => $request->route()?->getName(),
                    'status_code' => $response->getStatusCode(),
                    'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                    'ip_address' => $request->ip(),
                    'user_agent' => (string) $request->userAgent(),
                    'metadata' => [
                        'query_keys' => array_keys($request->query()),
                        'content_length' => $request->headers->get('content-length'),
                    ],
                ]);
            } catch (Throwable $exception) {
                Log::warning('Failed to write activity log.', [
                    'exception' => $exception->getMessage(),
                    'request_id' => $request->attributes->get(RequestId::ATTRIBUTE),
                ]);
            }
        }

        return $response;
    }

    private function shouldTrack(Request $request): bool
    {
        if (app()->runningUnitTests()) {
            return false;
        }

        return ! $request->is('build/*', 'favicon.ico', 'up');
    }
}
