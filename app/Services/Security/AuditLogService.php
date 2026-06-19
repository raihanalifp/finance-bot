<?php

namespace App\Services\Security;

use App\Enums\AuditAction;
use App\Http\Middleware\RequestId;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuditLogService
{
    public function record(
        AuditAction|string $action,
        ?Model $entity = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        array $context = [],
        ?int $userId = null,
    ): void {
        try {
            $request = request();
            $safeContext = $this->sanitize($context);

            AuditLog::query()->create([
                'user_id' => $userId ?? Auth::id(),
                'action' => $action instanceof AuditAction ? $action->value : $action,
                'entity_type' => $entity ? $entity::class : null,
                'entity_id' => $entity?->getKey() ? (string) $entity->getKey() : null,
                'old_values' => $oldValues ? $this->sanitize($oldValues) : null,
                'new_values' => $newValues ? $this->sanitize($newValues) : null,
                'context' => $safeContext,
                'ip_address' => $request instanceof Request ? $request->ip() : null,
                'user_agent' => $request instanceof Request ? (string) $request->userAgent() : null,
                'request_id' => $request instanceof Request ? $request->attributes->get(RequestId::ATTRIBUTE) : null,
                'created_at' => now(),
            ]);

            $channel = in_array($action instanceof AuditAction ? $action : AuditAction::tryFrom((string) $action), [AuditAction::SecurityBlocked], true)
                ? 'security'
                : 'audit';

            Log::channel($channel)->info('Audit event recorded.', [
                'action' => $action instanceof AuditAction ? $action->value : $action,
                'entity_type' => $entity ? $entity::class : null,
                'entity_id' => $entity?->getKey(),
                'request_id' => $request instanceof Request ? $request->attributes->get(RequestId::ATTRIBUTE) : null,
            ]);
        } catch (Throwable $exception) {
            Log::warning('Failed to write audit log.', ['exception' => $exception->getMessage()]);
        }
    }

    private function sanitize(array $values): array
    {
        $blockedKeys = ['password', 'token', 'secret', 'authorization', 'cookie', 'api_key', 'bot_token'];

        foreach ($values as $key => $value) {
            $normalizedKey = strtolower((string) $key);

            if (str_contains($normalizedKey, 'password') || str_contains($normalizedKey, 'token') || in_array($normalizedKey, $blockedKeys, true)) {
                $values[$key] = '[redacted]';
                continue;
            }

            if (is_array($value)) {
                $values[$key] = $this->sanitize($value);
            }
        }

        return $values;
    }
}
