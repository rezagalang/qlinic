<?php

namespace App\Enums;

enum PracticeSession: string
{
    case Pagi = 'pagi';
    case Sore = 'sore';
    case Malam = 'malam';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
