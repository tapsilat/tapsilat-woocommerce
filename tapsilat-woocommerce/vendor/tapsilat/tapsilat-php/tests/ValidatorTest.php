<?php
namespace Tapsilat\Tests;

use PHPUnit\Framework\TestCase;
use Tapsilat\Validators;
use Tapsilat\APIException;

class ValidatorTest extends TestCase
{
    // validateInstallments tests
    public function testEmptyStringReturnsDefault()
    {
        $result = Validators::validateInstallments("");
        $this->assertEquals([1], $result);
    }

    public function testNoneReturnsDefault()
    {
        $result = Validators::validateInstallments(null);
        $this->assertEquals([1], $result);
    }

    public function testValidSingleInstallment()
    {
        $result = Validators::validateInstallments("3");
        $this->assertEquals([3], $result);
    }

    public function testValidMultipleInstallments()
    {
        $result = Validators::validateInstallments("1,2,3,6");
        $this->assertEquals([1,2,3,6], $result);
    }

    public function testValidWithSpaces()
    {
        $result = Validators::validateInstallments("1, 2, 3, 6");
        $this->assertEquals([1,2,3,6], $result);
    }

    public function testInstallmentValueTooLow()
    {
        $this->expectException(APIException::class);
        Validators::validateInstallments("0,2,3");
    }

    public function testInstallmentValueTooHigh()
    {
        $this->expectException(APIException::class);
        Validators::validateInstallments("1,15,3");
    }

    public function testInvalidFormatLetters()
    {
        $this->expectException(APIException::class);
        Validators::validateInstallments("1,abc,3");
    }

    public function testInvalidFormatMixed()
    {
        $this->expectException(APIException::class);
        Validators::validateInstallments("1,2.5,3");
    }

    // validateGsmNumber tests
    public function testEmptyStringReturnsEmpty()
    {
        $result = Validators::validateGsmNumber("");
        $this->assertEquals("", $result);
    }

    public function testNoneReturnsNone()
    {
        $result = Validators::validateGsmNumber(null);
        $this->assertNull($result);
    }

    public function testValidInternationalPlusFormat()
    {
        $result = Validators::validateGsmNumber("+905551234567");
        $this->assertEquals("+905551234567", $result);
    }

    public function testValidInternational00Format()
    {
        $result = Validators::validateGsmNumber("00905551234567");
        $this->assertEquals("00905551234567", $result);
    }

    public function testValidNationalFormat()
    {
        $result = Validators::validateGsmNumber("05551234567");
        $this->assertEquals("05551234567", $result);
    }

    public function testValidLocalFormat()
    {
        $result = Validators::validateGsmNumber("5551234567");
        $this->assertEquals("5551234567", $result);
    }

    public function testRemovesFormattingCharacters()
    {
        $result = Validators::validateGsmNumber("+90 555 123-45(67)");
        $this->assertEquals("+905551234567", $result);
    }

    public function testInternationalPlusTooShort()
    {
        try {
            Validators::validateGsmNumber("+90123");
        } catch (APIException $e) {
            $this->assertEquals(0, $e->code);
            $this->assertStringContainsString("short", $e->error);
            return;
        }
        $this->fail("Expected APIException");
    }

    public function testInternational00TooShort()
    {
        try {
            Validators::validateGsmNumber("0090123");
        } catch (APIException $e) {
            $this->assertEquals(0, $e->code);
            $this->assertStringContainsString("short", $e->error);
            return;
        }
        $this->fail("Expected APIException");
    }

    public function testNationalTooShort()
    {
        try {
            Validators::validateGsmNumber("012345");
        } catch (APIException $e) {
            $this->assertEquals(0, $e->code);
            $this->assertStringContainsString("short", $e->error);
            return;
        }
        $this->fail("Expected APIException");
    }

    public function testLocalTooShort()
    {
        try {
            Validators::validateGsmNumber("12345");
        } catch (APIException $e) {
            $this->assertEquals(0, $e->code);
            $this->assertStringContainsString("short", $e->error);
            return;
        }
        $this->fail("Expected APIException");
    }

    public function testInvalidCharacters()
    {
        try {
            Validators::validateGsmNumber("+90abc1234567");
        } catch (APIException $e) {
            $this->assertEquals(0, $e->code);
            $this->assertStringContainsString("Invalid phone number format", $e->error);
            return;
        }
        $this->fail("Expected APIException");
    }

    public function testOnlySpecialCharacters()
    {
        $this->expectException(APIException::class);
        Validators::validateGsmNumber("+++---");
    }
}
