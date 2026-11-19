<?php

namespace App\Enums;

enum QueueStatus: string
{
    case Menunggu = 'menunggu';
    case Dipanggil = 'dipanggil';
    case Selesai = 'selesai';
    case Dilewati = 'dilewati';
    case Dibatalkan = 'dibatalkan';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /** Status yang masih berjalan (belum selesai/dibatalkan). */
    public static function activeValues(): array
    {
        return [self::Menunggu->value, self::Dipanggil->value];
    }
}
