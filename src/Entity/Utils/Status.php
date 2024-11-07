<?php

namespace App\Entity\Utils;

enum Status: int
{
    case OPEN = 1;
    case IN_PROGRESS = 2;
    case RESOLVE = 3;
    case CLOSE = 4;
}
