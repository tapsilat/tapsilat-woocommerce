<?php

namespace Tapsilat;

use InvalidArgumentException;

/**
 * A callback Tapsilat posts to an organization's callback URLs or to an order's payment_success_url
 * and payment_failure_url: {"type": "...", "data": {...}, "token": "<delivery id>"}.
 *
 * The shape of "data" depends on the type, which each organization chooses in the panel:
 * "order_lite" (the default), "order", "order_payment_detail", "extended_order" or
 * "payment_facilitator". The getters read the order's reference ID, conversation ID and status from
 * whichever shape arrived.
 *
 * Verify the Tapsilat-Signature header with TapsilatAPI::verifyWebhook() before trusting a callback.
 */
class WebhookEvent
{
    public const TYPE_ORDER = 'order';
    public const TYPE_ORDER_LITE = 'order_lite';
    public const TYPE_ORDER_PAYMENT_DETAIL = 'order_payment_detail';
    public const TYPE_EXTENDED_ORDER = 'extended_order';
    public const TYPE_PAYMENT_FACILITATOR = 'payment_facilitator';

    /** @var string|null */
    public $type;
    /** @var array The callback's "data" object, or the whole payload when it has none */
    public $data;
    /** @var string|null Unique per delivery */
    public $token;
    /** @var array The decoded payload */
    public $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
        $this->type = isset($payload['type']) && is_string($payload['type']) ? $payload['type'] : null;
        $this->token = isset($payload['token']) && is_string($payload['token']) ? $payload['token'] : null;
        $this->data = isset($payload['data']) && is_array($payload['data']) ? $payload['data'] : $payload;
    }

    /**
     * Decodes a raw callback body.
     *
     * @param string $payload
     * @return self
     * @throws InvalidArgumentException when the body is not a JSON object
     */
    public static function fromPayload($payload)
    {
        $decoded = json_decode((string)$payload, true);
        if (!is_array($decoded)) {
            throw new InvalidArgumentException('the webhook payload is not a JSON object');
        }
        return new self($decoded);
    }

    /**
     * @return string|null The order's reference ID
     */
    public function getReferenceId()
    {
        $nested = isset($this->data['order']) && is_array($this->data['order']) ? $this->data['order'] : [];
        return self::firstString([
            self::value($this->data, 'orderReferenceId'),
            self::value($this->data, 'order_reference_id'),
            self::value($this->data, 'reference_id'),
            self::value($nested, 'reference_id'),
        ]);
    }

    /**
     * @return string|null The conversation ID the order was created with
     */
    public function getConversationId()
    {
        return self::firstString([
            self::value($this->data, 'conversationId'),
            self::value($this->data, 'conversation_id'),
        ]);
    }

    /**
     * @return int|null The order status code (see OrderStatus), or null when this type carries none
     */
    public function getStatusCode()
    {
        $code = OrderStatus::code(self::value($this->data, 'status'));
        if ($code === null && isset($this->data['order']) && is_array($this->data['order'])) {
            $code = OrderStatus::code(self::value($this->data['order'], 'status'));
        }
        return $code;
    }

    /**
     * @return string|null The order status name, such as "Paid"
     */
    public function getStatus()
    {
        return OrderStatus::name($this->getStatusCode());
    }

    public function isPaid()
    {
        return OrderStatus::isPaid($this->getStatusCode());
    }

    public function isFailed()
    {
        return OrderStatus::isFailed($this->getStatusCode());
    }

    public function isRefunded()
    {
        return OrderStatus::isRefunded($this->getStatusCode());
    }

    private static function value(array $data, $key)
    {
        return array_key_exists($key, $data) ? $data[$key] : null;
    }

    private static function firstString(array $values)
    {
        foreach ($values as $value) {
            if (is_string($value) && $value !== '' && $value !== '00000000-0000-0000-0000-000000000000') {
                return $value;
            }
            if (is_int($value)) {
                return (string)$value;
            }
        }
        return null;
    }
}
