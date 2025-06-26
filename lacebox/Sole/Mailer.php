<?php

namespace Lacebox\Sole;

use Lacebox\Sole\Mail\MailgunDriver;
use Lacebox\Sole\Mail\PhpMailDriver;
use Lacebox\Sole\Mail\SmtpDriver;

class Mailer
{
    protected $driver;

    public function __construct()
    {
        $cfg    = config('mail');
        $from   = $cfg['from'];
        switch ($cfg['driver']) {
            case 'smtp':
                $this->driver = new SmtpDriver($cfg['smtp']);
                break;
            case 'mailgun':
                $this->driver = new MailgunDriver($cfg['mailgun']);
                break;
            case 'php_mail':
            default:
                $this->driver = new PhpMailDriver();
                break;
        }
    }

    /**
     * Send an email.
     */
    public function send(
        string $to,
        string $subject,
        string $html,
        array $from = null,
        array $headers = [],
        array $attachments = []
    ) {
        return $this->driver->send($to, $subject, $html, $from, $headers, $attachments);
    }

    /** Shortcut */
    public static function to(string $to)
    {
        return new class($to) {
            protected $to;
            protected $subject;
            protected $html;
            protected $from;
            public function __construct($to) { $this->to = $to; }
            public function subject($s) { $this->subject = $s; return $this; }
            public function view($view, $data=[])
            {
                $this->html = view($view, $data);
                return $this;
            }
            public function from($address, $name = null)
            {
                $this->from = [$address, $name ?: ''];
                return $this;
            }
            public function send()
            {
                return (new Mailer())->send(
                    $this->to, $this->subject, $this->html, $this->from
                );
            }
        };
    }
}