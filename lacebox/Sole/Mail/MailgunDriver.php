<?php

/**
 * LacePHP
 *
 * This file is part of the LacePHP framework.
 *
 * (c) 2025 OpenSourceAfrica
 *     Author : Akinyele Olubodun
 *     Website: https://www.lacephp.com
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

class MailgunDriver implements MailDriverInterface
{
    protected $domain;
    protected $apiKey;
    protected $endpoint;

    public function __construct(array $cfg = [])
    {
        $this->domain   = $cfg['domain']   ?? '';
        $this->apiKey   = $cfg['api_key']  ?? '';
        $this->endpoint = rtrim($cfg['endpoint'] ?? '', '/');
    }

    public function send(
        string $to,
        string $subject,
        string $html,
               $from = null,
        array $headers = [],
        array $attachments = []
    ) {
        $fromAddr = is_array($from) ? "{$from[1]} <{$from[0]}>" : ($from ?: config('mail.from.address'));
        $post = [
            'from'    => $fromAddr,
            'to'      => $to,
            'subject' => $subject,
            'html'    => $html,
        ];
        // mailgun doesn’t allow custom headers via form, skipping attachments

        $url = $this->endpoint . "/{$this->domain}/messages";
        // basic curl
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => 'api:' . $this->apiKey,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $post,
        ]);
        $resp = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);

        return $err ? false : $resp;
    }
}