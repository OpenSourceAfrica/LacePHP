<?php

namespace Lacebox\Strap\Guards;

use Lacebox\Shoelace\Guards\ShoeGuardInterface;

/**
 * ShoeHmacGuard verifies an HMAC signature on the request body.
 */
class ShoeHmacGuard implements ShoeGuardInterface
{
    protected $signature;
    protected $secret;
    protected $payload;

    public function __construct(string $secret)
    {
        $this->secret    = $secret;
        $this->signature = $_SERVER['HTTP_X_HMAC_SIGNATURE'] ?? '';
        $this->payload   = file_get_contents('php://input');
    }

    public function check(): bool
    {
        if (empty($this->signature)) {
            return false;
        }
        $expected = hash_hmac('sha256', $this->payload, $this->secret);
        return hash_equals($expected, $this->signature);
    }
}