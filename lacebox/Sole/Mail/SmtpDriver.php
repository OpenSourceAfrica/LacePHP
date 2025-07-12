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

class SmtpDriver implements MailDriverInterface
{
    protected $host;
    protected $port;
    protected $user;
    protected $pass;
    protected $encryption;

    public function __construct(array $cfg = [])
    {
        $this->host       = $cfg['host']       ?? 'localhost';
        $this->port       = $cfg['port']       ?? 25;
        $this->user       = $cfg['username']   ?? '';
        $this->pass       = $cfg['password']   ?? '';
        $this->encryption = $cfg['encryption'] ?? '';
    }

    public function send(
        string $to,
        string $subject,
        string $html,
               $from = null,
        array $headers = [],
        array $attachments = []
    ) {
        // Very basic SMTP via fsockopen
        $fp = fsockopen(
            ($this->encryption==='ssl'?'ssl://':'') . $this->host,
            $this->port,
            $errno, $errstr, 10
        );
        if (! $fp) {
            return false;
        }

        $this->getResponse($fp); // read banner

        $this->sendCmd($fp, "EHLO " . gethostname());
        if ($this->encryption==='tls') {
            $this->sendCmd($fp, "STARTTLS");
            stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $this->sendCmd($fp, "EHLO " . gethostname());
        }

        if ($this->user) {
            $this->sendCmd($fp, "AUTH LOGIN");
            $this->sendCmd($fp, base64_encode($this->user));
            $this->sendCmd($fp, base64_encode($this->pass));
        }

        $fromAddr = is_array($from) ? $from[0] : ($from ?: config('mail.from.address'));
        $this->sendCmd($fp, "MAIL FROM:<{$fromAddr}>");
        $this->sendCmd($fp, "RCPT TO:<{$to}>");
        $this->sendCmd($fp, "DATA");

        // Headers + body
        $hdrs = "Subject: {$subject}\r\n";
        $hdrs .= "From: {$fromAddr}\r\n";
        $hdrs .= "MIME-Version: 1.0\r\n";
        $hdrs .= "Content-Type: text/html; charset=UTF-8\r\n";
        foreach ($headers as $k=>$v) {
            $hdrs .= "{$k}: {$v}\r\n";
        }
        $hdrs .= "\r\n" . $html . "\r\n.";

        $this->sendCmd($fp, $hdrs);
        $this->sendCmd($fp, "QUIT");
        fclose($fp);

        return true;
    }

    protected function sendCmd($fp, $cmd)
    {
        fwrite($fp, $cmd . "\r\n");
        return $this->getResponse($fp);
    }

    protected function getResponse($fp)
    {
        $resp = '';
        while ($line = fgets($fp, 515)) {
            $resp .= $line;
            if (substr($line,3,1) === ' ') break;
        }
        return $resp;
    }
}
