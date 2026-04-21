<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin      = 'admin';
    case Attendant  = 'attendant';
    case Mechanic   = 'mechanic';
    case Storekeeper = 'storekeeper';
    case Purchasing  = 'purchasing';

    public function label(): string
    {
        return match($this) {
            self::Admin       => 'Administrador',
            self::Attendant   => 'Atendente',
            self::Mechanic    => 'Mecânico',
            self::Storekeeper => 'Almoxarife',
            self::Purchasing  => 'Compras',
        };
    }
}
