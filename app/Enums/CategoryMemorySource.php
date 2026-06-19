<?php

namespace App\Enums;

enum CategoryMemorySource: string
{
    case UserConfirmed = 'user_confirmed';
    case SystemSeeded = 'system_seeded';
    case AiSuggested = 'ai_suggested';
}
