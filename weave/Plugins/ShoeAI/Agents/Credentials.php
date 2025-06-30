<?php

namespace Weave\Plugins\ShoeAI\Agents;

class Credentials
{
    const CONFIG_KEY = 'ai.token';

    public static function enable(): void
    {
        // 0) Load the merged config
        $cfg = config();

        // 1) If we already have a license_key and enabled=true, bail out
        if (! empty($cfg['ai']['enabled']) && ! empty($cfg['ai']['license_key'])) {
            fwrite(STDOUT, "✅ You’re already activated (license: {$cfg['ai']['license_key']}).\n");
            return;
        }

        fwrite(STDOUT, "👤 Your License Key: ");
        $license_key = trim(fgets(STDIN));

        // call your registration endpoint
        $http = new HttpClient();
        $resp = $http->post('/activate.php', [
            'hwid'    => lace_hwid($license_key),
            'license' => $license_key,
            'version' => config('sole_version'),
        ]);

        if ($resp['status'] !== 200) {
            fwrite(STDERR, "❌ Activation failed: {$resp['body']}\n");
            exit(1);
        }

        $data  = json_decode($resp['body'], true);
        $token = $data['token'] ?? null;
        if (! $token) {
            fwrite(STDERR, "❌ No token returned\n");
            exit(1);
        }

        // persist into lace.json under "ai.token"
        $cfg = config();
        $cfg['ai']['enabled'] = true;
        $cfg['ai']['token']   = $token;
        file_put_contents(
            dirname(__DIR__,3) . '/lace.json',
            json_encode($cfg, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)
        );

        fwrite(STDOUT, "✅ AI plugin enabled. Token saved.\n");
    }

    public static function token(): ?string
    {
        return config(self::CONFIG_KEY, null);
    }
}