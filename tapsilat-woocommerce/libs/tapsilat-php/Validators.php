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
        if ($input === '' || $input === null) {
            return $input;
        }

        // Clean common spacing symbols
        $cleaned = preg_replace('/[\s\-\(\)]/', '', $input);

        // Check if it's purely digits (allowing a leading '+')
        if (!preg_match('/^\+?\d+$/', $cleaned)) {
            throw new APIException(0, 0, 'Invalid phone number format');
        }

        // Basic universal length verification
        if (strlen(str_replace('+', '', $cleaned)) < 5) {
            throw new APIException(0, 0, 'Phone number is too short');
        }

        return $cleaned;
    }
}
