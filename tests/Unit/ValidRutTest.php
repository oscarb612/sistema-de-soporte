<?php

namespace Tests\Unit;

use App\Rules\ValidRut;
use PHPUnit\Framework\TestCase;

class ValidRutTest extends TestCase
{
    public function test_it_accepts_valid_chilean_rut(): void
    {
        $this->assertTrue(ValidRut::isValid('12.345.678-5'));
        $this->assertTrue(ValidRut::isValid('12345678-5'));
    }

    public function test_it_rejects_invalid_chilean_rut(): void
    {
        $this->assertFalse(ValidRut::isValid('12.345.678-9'));
        $this->assertFalse(ValidRut::isValid('abc'));
    }
}
