<?php

namespace Lacebox\Shoelace\Guards;

/**
 * ShoeGuardInterface defines a contract for shoe-themed authentication/authorization guards.
 */
interface ShoeGuardInterface
{
    /**
     * Perform the guard check.
     *
     * @return bool True if the check passes, false otherwise.
     */
    public function check(): bool;
}