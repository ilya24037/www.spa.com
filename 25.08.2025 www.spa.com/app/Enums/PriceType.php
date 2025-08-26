<?php

namespace App\Enums;

enum PriceType: string
{
    case FIXED = 'FIXED';
    case HOURLY = 'HOURLY';
    case FROM = 'FROM';
    case NEGOTIABLE = 'NEGOTIABLE';
}