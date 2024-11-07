<?php

namespace App\Entity\Utils;

enum Priority: string
{
    case LOW = 'Basse';
    case MEDIUM = 'Moyenne';
    case HIGH = 'Haute';
}
