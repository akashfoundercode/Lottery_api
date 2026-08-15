<?php

namespace App\Enums;

enum BookStatus: string
{
    case AVAILABLE = 'available';
    case ASSIGNED = 'assigned';
    case IN_PROGRESS = 'in_progress';
    case SOLD = 'sold';
    case UNSOLD = 'unsold';
    case UNSOLD_BY_ADMIN = 'unsold_by_admin';
}