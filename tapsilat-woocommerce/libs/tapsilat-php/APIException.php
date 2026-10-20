<?php

namespace Tapsilat;

use Exception;

class APIException extends Exception
{
    public $statusCode;
    public $code;
    public $error;
    /** @var string|null The response body's "status" field, when it has one */
    public $status;
    /** @var array|null Per-field validation messages, when the API returns them */
    public $errors;
    /** @var string|null The raw response body */
    public $rawBody;

    public function __construct($statusCode, $code, $error, $status = null, $errors = null, $rawBody = null)
    {
        parent::__construct(
            "Tapsilat API Error\nstatus_code:{$statusCode}\ncode:{$code}\nerror:{$error}",
            is_numeric($code) ? (int)$code : 0
        );
        $this->statusCode = $statusCode;
        $this->code = $code;
        $this->error = $error;
        $this->status = $status;
        $this->errors = $errors;
        $this->rawBody = $rawBody;
    }

    /**
     * Builds the exception from an error response body such as {"error": "...", "code": 5} or
     * {"message": "INVALID_REQUEST_BODY", "code": 400000, "errors": {...}}.
     */
    public static function fromResponse($statusCode, $body)
    {
        $body = (string)$body;
        $data = json_decode($body, true);
        if (!is_array($data)) {
            $message = trim($body) !== '' ? $body : 'HTTP error';
            return new self($statusCode, -1, $message, null, null, $body);
        }

        $code = array_key_exists('code', $data) && is_scalar($data['code']) ? $data['code'] : -1;
        if (isset($data['message']) && is_scalar($data['message'])) {
            $message = (string)$data['message'];
        } elseif (isset($data['error']) && is_scalar($data['error'])) {
            $message = (string)$data['error'];
        } else {
            $message = 'HTTP error';
        }
        $status = isset($data['status']) && is_scalar($data['status']) ? (string)$data['status'] : null;
        $errors = isset($data['errors']) && is_array($data['errors']) ? $data['errors'] : null;

        return new self($statusCode, $code, $message, $status, $errors, $body);
    }

    /**
     * Builds the exception for a response that is not JSON, such as the panel's HTML page.
     */
    public static function unexpectedResponse($statusCode, $contentType, $body)
    {
        $message = sprintf(
            'unexpected non-JSON response (Content-Type "%s"); check that the endpoint URL and route are correct',
            $contentType
        );
        return new self($statusCode, -1, $message, null, null, (string)$body);
    }
}
