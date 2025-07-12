<?php

/**
 * LacePHP AI Plugin
 *
 * This plugin is part of the LacePHP framework.
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

namespace Weave\Plugins\ShoeAI\Agents;

class HttpClient
{
    protected $base;
    protected $path;
    protected $payload = [];

    public function __construct()
    {
        //$this->base = 'https://ai.lacephp.com/v1';
        $this->base = 'https://f2b45ccdc53b.ngrok-free.app';
    }

    public function post(string $path, array $data): array
    {
        $this->path    = $path;
        $this->payload = $data;
        return $this->send();
    }

    protected function send(): array
    {
        $url = $this->base . $this->path;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $hdr = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . Credentials::token(),
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $hdr);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->payload));
        $body   = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['status'=>$status,'body'=>$body];
    }
}