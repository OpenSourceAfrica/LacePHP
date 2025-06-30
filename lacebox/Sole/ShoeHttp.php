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

namespace Lacebox\Sole;

class ShoeHttp
{
    protected $url;
    protected $method = 'GET';
    protected $headers = [];
    protected $curlOpts = [];
    protected $body = null;

    public function __construct(string $url = '')
    {
        if ($url) {
            $this->url($url);
        }
    }

    /** Set the URL */
    public function url(string $u): self
    {
        $this->url = $u;
        return $this;
    }

    /** HTTP verb */
    public function method(string $verb): self
    {
        $this->method = strtoupper($verb);
        return $this;
    }

    /** Add or overwrite a header */
    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /** Basic auth */
    public function authBasic(string $user, string $pass): self
    {
        $this->curlOpts[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
        $this->curlOpts[CURLOPT_USERPWD]  = "{$user}:{$pass}";
        return $this;
    }

    /** Bearer token auth */
    public function authBearer(string $token): self
    {
        return $this->header('Authorization', "Bearer {$token}");
    }

    /** Digest auth */
    public function authDigest(string $user, string $pass): self
    {
        $this->curlOpts[CURLOPT_HTTPAUTH] = CURLAUTH_DIGEST;
        $this->curlOpts[CURLOPT_USERPWD]  = "{$user}:{$pass}";
        return $this;
    }

    /** SOAP action (and JSON-XML content type) */
    public function soap(string $action): self
    {
        $this->header('Content-Type', 'text/xml; charset=utf-8');
        $this->header('SOAPAction', $action);
        return $this;
    }

    /** Send form-data (multipart) */
    public function formData(array $fields): self
    {
        $this->body = $fields;
        // let curl set its own multipart headers
        unset($this->headers['Content-Type']);
        return $this;
    }

    /** Send raw JSON */
    public function json($data): self
    {
        $this->body = json_encode($data);
        $this->header('Content-Type', 'application/json');
        return $this;
    }

    /** Send raw text or HTML */
    public function raw(string $data, string $contentType = 'text/plain'): self
    {
        $this->body = $data;
        $this->header('Content-Type', $contentType);
        return $this;
    }

    /** Additional custom curl options */
    public function option(int $key, $value): self
    {
        $this->curlOpts[$key] = $value;
        return $this;
    }

    /**
     * Execute the request.
     * @return array ['status'=>int, 'headers'=>array, 'body'=>string]
     */
    public function send(): array
    {
        if (empty($this->url)) {
            throw new \RuntimeException("No URL set for request");
        }
        $ch = curl_init();

        // Basic settings
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->method);

        // Body
        if ($this->body !== null) {
            // multipart if array
            if (is_array($this->body)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->body);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->body);
            }
        }

        // Headers
        if (! empty($this->headers)) {
            $hdrs = [];
            foreach ($this->headers as $k => $v) {
                $hdrs[] = "{$k}: {$v}";
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $hdrs);
        }

        // Custom curl opts (auth, timeouts, etc.)
        foreach ($this->curlOpts as $opt => $val) {
            curl_setopt($ch, $opt, $val);
        }

        // Execute
        $raw  = curl_exec($ch);
        $info = curl_getinfo($ch);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new \RuntimeException("cURL error: {$err}");
        }

        // Separate headers / body
        $hSize = $info['header_size'] ?? 0;
        $rawH  = substr($raw, 0, $hSize);
        $body  = substr($raw, $hSize);

        // Parse response headers into array
        $lines   = preg_split("/\r\n/", trim($rawH));
        $status  = $info['http_code'] ?? 0;
        $respHdr = [];
        array_shift($lines); // remove HTTP/1.x line
        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                list($k, $v) = explode(':', $line, 2);
                $respHdr[trim($k)] = trim($v);
            }
        }

        return [
            'status'  => $status,
            'headers' => $respHdr,
            'body'    => $body,
        ];
    }
}