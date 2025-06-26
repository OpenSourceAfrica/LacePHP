<?php
namespace Lacebox\Strap\Guards;

use Lacebox\Shoelace\Guards\ShoeGuardInterface;

/**
 * ShoeSignatureGuard verifies a signature passed as a query parameter.
 */
class ShoeSignatureGuard implements ShoeGuardInterface
{
    protected $signature;
    protected $secret;
    protected $params;

    public function __construct(string $secret)
    {
        $this->secret    = $secret;
        $this->params    = $_GET;
        $this->signature = $this->params['signature'] ?? '';
    }

    public function check(): bool
    {
        if (empty($this->signature)) {
            return false;
        }
        $data = $this->params;
        unset($data['signature']);
        ksort($data);
        $query    = http_build_query($data);
        $expected = hash_hmac('sha256', $query, $this->secret);
        return hash_equals($expected, $this->signature);
    }
}