<?php

namespace App\Enums;

enum AuditAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Login = 'login';
    case SecurityBlocked = 'security_blocked';
    case TelegramProcessed = 'telegram_processed';
    case BudgetAlertSent = 'budget_alert_sent';
}
