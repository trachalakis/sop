<?php

declare(strict_types=1);

namespace Domain\Enums;

enum UserRole: string
{
    case webmaster = 'webmaster';
    case warmCuisine = 'warm_cuisine';
    case coldCuisine = 'cold_cuisine';
    case prep = 'prep';
    case cleaning = 'cleaning';

    case waiter = 'waiter';
    case reservationManager = 'reservations_manager';
    case assistantWaiter = 'assistant_waiter';
    case barManager = 'bar_manager';
    case barAssistant = 'bar_assistant';
}