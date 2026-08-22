<?php

namespace App\WorkItems;

enum StatusWidget: string
{
    case TaskSelect = 'task_select';
    case BinarySelect = 'binary_select';
    case Badge = 'badge';
}
