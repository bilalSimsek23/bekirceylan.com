<?php

namespace App\Rules;

use App\Models\Customer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TcKimlikNo implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (filled($value) && ! Customer::isValidTcNo((string) $value)) {
            $fail('Geçersiz TC Kimlik Numarası.');
        }
    }
}
