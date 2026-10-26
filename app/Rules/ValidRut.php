<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidRut implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! self::isValid($value)) {
            $fail('El RUT ingresado no es válido.');
        }
    }

    public static function isValid(string $rut): bool
    {
        $cleanRut = self::clean($rut);

        if (strlen($cleanRut) < 2) {
            return false;
        }

        $body = substr($cleanRut, 0, -1);
        $verificationDigit = substr($cleanRut, -1);

        if ($body === '' || ! ctype_digit($body)) {
            return false;
        }

        $sum = 0;
        $multiplier = 2;

        for ($index = strlen($body) - 1; $index >= 0; $index--) {
            $sum += (int) $body[$index] * $multiplier;
            $multiplier = $multiplier === 7 ? 2 : $multiplier + 1;
        }

        $expected = 11 - ($sum % 11);
        $expectedDigit = match ($expected) {
            11 => '0',
            10 => 'K',
            default => (string) $expected,
        };

        return $verificationDigit === $expectedDigit;
    }

    public static function clean(string $rut): string
    {
        return strtoupper(preg_replace('/[^0-9K]/', '', $rut) ?? '');
    }

    public static function format(?string $rut): string
    {
        if ($rut === null || trim($rut) === '') {
            return '';
        }

        $cleanRut = self::clean($rut);

        if (strlen($cleanRut) < 2) {
            return $rut;
        }

        $body = substr($cleanRut, 0, -1);
        $verificationDigit = substr($cleanRut, -1);

        if ($body === '' || ! ctype_digit($body)) {
            return $rut;
        }

        return number_format((int) $body, 0, '', '.') . '-' . $verificationDigit;
    }
}
