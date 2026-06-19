<?php

namespace App\Enums;

enum TransactionLogStatus: string
{
    case Received = 'received';
    case Parsed = 'parsed';
    case Processed = 'processed';
    case Ignored = 'ignored';
    case Failed = 'failed';
}
