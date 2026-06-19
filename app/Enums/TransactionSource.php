<?php

namespace App\Enums;

enum TransactionSource: string
{
    case Telegram = 'telegram';
    case Dashboard = 'dashboard';
    case Import = 'import';
    case System = 'system';
}
