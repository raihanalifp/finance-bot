<?php

namespace App\Http\Middleware;

use App\Enums\AuditAction;
use App\Services\Security\AuditLogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDashboardAccess
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $configuredPassword = config('security.dashboard.password');

        if (blank($configuredPassword) && app()->isLocal()) {
            return $next($request);
        }

        if (blank($configuredPassword)) {
            abort(503, 'Dashboard password is not configured.');
        }

        $username = (string) $request->getUser();
        $password = (string) $request->getPassword();

        if (
            hash_equals((string) config('security.dashboard.username'), $username)
            && hash_equals((string) $configuredPassword, $password)
        ) {
            return $next($request);
        }

        $this->auditLogService->record(AuditAction::SecurityBlocked, context: [
            'reason' => 'dashboard_basic_auth_failed',
            'path' => $request->path(),
            'username' => $username,
        ]);

        return response('Authentication required.', 401, [
            'WWW-Authenticate' => 'Basic realm="Finance Bot Dashboard"',
        ]);
    }
}
