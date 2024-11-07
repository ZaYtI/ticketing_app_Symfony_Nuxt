<?php

namespace App\Entity\Utils;

enum Status: string
{
    case OPEN = 'Ouvert';
    case IN_PROGRESS = 'En cours';
    case RESOLVE = 'Résolue';
    case CLOSE = 'Fermé';
}
