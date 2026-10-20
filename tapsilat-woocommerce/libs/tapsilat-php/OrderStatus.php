<?php

namespace Tapsilat;

/**
 * Order status codes, as callbacks and order details carry them, and the names that
 * GET /order/{reference_id}/status returns for them.
 */
final class OrderStatus
{
    public const RECEIVED = 1;
    public const UNPAID = 2;
    public const PAID = 3;
    public const PROCESSING = 4;
    public const SHIPPED = 5;
    public const ON_HOLD = 6;
    public const WAITING_FOR_PAYMENT = 7;
    public const CANCELLED = 8;
    public const COMPLETED = 9;
    public const REFUNDED = 10;
    public const FRAUD = 11;
    public const REJECTED = 12;
    public const FAILURE = 13;
    public const RETRYING = 14;
    public const PARTIALLY_REFUNDED = 15;
    public const SUB_MERCHANT_PAYMENT_APPROVED = 16;
    public const SUB_MERCHANT_PAYMENT_DISAPPROVED = 17;
    public const SUB_MERCHANT_PAYMENT_ERRORED = 18;
    public const STILL_HAS_UNPAID_INSTALLMENTS = 19;
    public const STILL_HAS_UNPAID_TERMS = 20;
    public const EXPIRED = 21;
    public const STILL_HAS_UNPAID_SUB_MERCHANT_PAYMENTS = 22;
    public const PARTIALLY_PAID = 23;
    public const TERMINATED = 24;
    public const CARD_TOKENIZATION = 25;
    public const PRE_AUTHORIZED = 26;
    public const DISPUTED = 27;
    public const PARTIALLY_DISPUTED = 28;
    public const SUSPECT = 29;

    public const NAMES = [
        self::RECEIVED => 'Received',
        self::UNPAID => 'Unpaid',
        self::PAID => 'Paid',
        self::PROCESSING => 'Processing',
        self::SHIPPED => 'Shipped',
        self::ON_HOLD => 'On hold',
        self::WAITING_FOR_PAYMENT => 'Waiting for payment',
        self::CANCELLED => 'Cancelled',
        self::COMPLETED => 'Completed',
        self::REFUNDED => 'Refunded',
        self::FRAUD => 'Fraud',
        self::REJECTED => 'Rejected',
        self::FAILURE => 'Failure',
        self::RETRYING => 'Retrying',
        self::PARTIALLY_REFUNDED => 'Partially refunded',
        self::SUB_MERCHANT_PAYMENT_APPROVED => 'Sub merchant payment approved',
        self::SUB_MERCHANT_PAYMENT_DISAPPROVED => 'Sub merchant payment disapproved',
        self::SUB_MERCHANT_PAYMENT_ERRORED => 'Sub merchant payment errored',
        self::STILL_HAS_UNPAID_INSTALLMENTS => 'Still has unpaid installments',
        self::STILL_HAS_UNPAID_TERMS => 'Still has unpaid terms',
        self::EXPIRED => 'Expired',
        self::STILL_HAS_UNPAID_SUB_MERCHANT_PAYMENTS => 'Still has unpaid sub merchant payments',
        self::PARTIALLY_PAID => 'Partially Paid',
        self::TERMINATED => 'Terminated',
        self::CARD_TOKENIZATION => 'Card Tokenization',
        self::PRE_AUTHORIZED => 'Pre Authorized',
        self::DISPUTED => 'Disputed',
        self::PARTIALLY_DISPUTED => 'Partially disputed',
        self::SUSPECT => 'Suspect',
    ];

    /** Statuses of an order whose payment was captured. */
    public const PAID_STATUSES = [self::PAID, self::COMPLETED, self::SUB_MERCHANT_PAYMENT_APPROVED];

    /** Statuses of an order that will not be paid. */
    public const FAILED_STATUSES = [
        self::CANCELLED, self::FRAUD, self::REJECTED, self::FAILURE, self::EXPIRED, self::TERMINATED,
    ];

    /** Statuses of an order whose payment was refunded in full or in part. */
    public const REFUNDED_STATUSES = [self::REFUNDED, self::PARTIALLY_REFUNDED];

    private function __construct()
    {
    }

    /**
     * Returns the status code for a code, a numeric string such as order_lite callbacks send, or
     * a status name such as "Paid" (case-insensitive), or null when the value is not a status.
     *
     * @param int|string|null $status
     * @return int|null
     */
    public static function code($status)
    {
        if (is_int($status)) {
            return isset(self::NAMES[$status]) ? $status : null;
        }
        if (!is_string($status)) {
            return null;
        }
        $status = trim($status);
        if (ctype_digit($status)) {
            return self::code((int)$status);
        }
        foreach (self::NAMES as $code => $name) {
            if (strcasecmp($name, $status) === 0) {
                return $code;
            }
        }
        return null;
    }

    /**
     * Returns the status name, such as "Paid", or null when the value is not a status.
     *
     * @param int|string|null $status
     * @return string|null
     */
    public static function name($status)
    {
        $code = self::code($status);
        return $code === null ? null : self::NAMES[$code];
    }

    /**
     * @param int|string|null $status
     */
    public static function isPaid($status)
    {
        return in_array(self::code($status), self::PAID_STATUSES, true);
    }

    /**
     * @param int|string|null $status
     */
    public static function isFailed($status)
    {
        return in_array(self::code($status), self::FAILED_STATUSES, true);
    }

    /**
     * @param int|string|null $status
     */
    public static function isRefunded($status)
    {
        return in_array(self::code($status), self::REFUNDED_STATUSES, true);
    }
}
