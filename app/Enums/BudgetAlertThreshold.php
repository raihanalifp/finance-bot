<?php

namespace App\Enums;

enum BudgetAlertThreshold: int
{
    case Warning80 = 80;
    case Exceeded100 = 100;
}
