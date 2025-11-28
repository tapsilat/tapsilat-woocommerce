<?php
namespace Tapsilat;

class Validators
{
    public static function validateInstallments($input)
    {
        if ($input === '' || $input === null) {
            return [1];
        }
        $parts = preg_split('/\s*,\s*/', $input);
        $result = [];
        foreach ($parts as $part) {
            if (!preg_match('/^\d+$/', $part)) {
                throw new APIException(0, 0, 'Invalid installment format');
            }
            $num = intval($part);
            if ($num < 1) {
                throw new APIException(0, 0, 'Installment value too low');
            }
            if ($num > 12) {
                throw new APIException(0, 0, 'Installment value too high');
            }
            $result[] = $num;
        }
        return $result;
    }

    public static function validateGsmNumber($input)
    {
        if ($input === '') {
            return '';
        }
        if ($input === null) {
            return null;
        }
        $cleaned = preg_replace('/[\s\-\(\)]/', '', $input);
        if (preg_match('/^\+90\d{10}$/', $cleaned) || preg_match('/^0090\d{10}$/', $cleaned) || preg_match('/^0\d{10}$/', $cleaned) || preg_match('/^\d{10}$/', $cleaned)) {
            return $cleaned;
        }
        if (preg_match('/^\+90\d{1,9}$/', $cleaned) || preg_match('/^0090\d{1,9}$/', $cleaned) || preg_match('/^0\d{1,9}$/', $cleaned) || preg_match('/^\d{1,9}$/', $cleaned)) {
            throw new APIException(0, 0, 'Phone number too short');
        }
        if (!preg_match('/^[\d\+]+$/', $cleaned)) {
            throw new APIException(0, 0, 'Invalid phone number format');
        }
        throw new APIException(0, 0, 'Invalid phone number format');
    }
}
