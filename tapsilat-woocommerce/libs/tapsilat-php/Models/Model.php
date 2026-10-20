<?php

namespace Tapsilat\Models;

/**
 * Base for request models built from an associative array of field values, for example
 * new CardInstallmentsRequest(['bin' => '54066200', 'amount' => 100, 'currency' => 'TRY']).
 * Unknown field names are rejected, and fields left null are omitted from the request.
 */
abstract class Model
{
    public function __construct(array $fields = [])
    {
        foreach ($fields as $name => $value) {
            if (!property_exists($this, $name)) {
                throw new \InvalidArgumentException(sprintf('%s has no field "%s"', static::class, $name));
            }
            $this->$name = $value;
        }
    }

    public function toArray()
    {
        $result = [];
        foreach (get_object_vars($this) as $key => $value) {
            if ($value !== null) {
                $result[$key] = self::export($value);
            }
        }
        return $result;
    }

    private static function export($value)
    {
        if (is_object($value) && method_exists($value, 'toArray')) {
            return $value->toArray();
        }
        if (is_array($value)) {
            return array_map([self::class, 'export'], $value);
        }
        return $value;
    }
}
