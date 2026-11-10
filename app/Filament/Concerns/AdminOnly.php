<?php

namespace App\Filament\Concerns;

/** Sadece "Yönetici" rolündeki kullanıcıların erişebildiği kaynak / sayfalar. */
trait AdminOnly
{
    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }
}
