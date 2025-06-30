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

namespace Lacebox\Sole\Mail;

use Lacebox\Shoelace\MailDriverInterface;

class PhpMailDriver implements MailDriverInterface
{
    public function send(
        string $to,
        string $subject,
        string $html,
               $from = null,
        array $headers = [],
        array $attachments = []
    ) {
        // Build headers
        $hdrs = [];
        $sender = $from ?: config('mail.from.address');
        if (is_array($sender)) {
            list($addr, $name) = $sender;
            $hdrs[] = "From: {$name} <{$addr}>";
        } else {
            $hdrs[] = "From: {$sender}";
        }
        $hdrs[] = 'MIME-Version: 1.0';
        $hdrs[] = 'Content-Type: text/html; charset=UTF-8';

        foreach ($headers as $k => $v) {
            $hdrs[] = "{$k}: {$v}";
        }

        // NOTE: attachments aren’t supported with mail()
        return mail($to, $subject, $html, implode("\r\n", $hdrs));
    }
}
