<?php

/**
 * LacePHP
 *
 * This file is part of the LacePHP framework.
 *
 * (c) 2025 OpenSourceAfrica
 *     Author : Akinyele Olubodun
 *     Website: https://www.akinyeleolubodun.com
 *
 * @link    https://github.com/OpenSourceAfrica/LacePHP
 * @license MIT
 * SPDX-License-Identifier: MIT
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Lacebox\Shoelace;

interface MailDriverInterface
{
    /**
     * Send a message.
     *
     * @param string       $to       Recipient email
     * @param string       $subject  Subject line
     * @param string       $html     HTML body
     * @param array|string $from     Optional from [address,name] or string
     * @param array        $headers  Extra headers
     * @param array        $attachments  File paths to attach
     * @return bool|string          True on success (or response), false on failure
     */
    public function send(
        string $to,
        string $subject,
        string $html,
               $from = null,
        array $headers = [],
        array $attachments = []
    );
}
