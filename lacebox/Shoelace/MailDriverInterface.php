<?php

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
