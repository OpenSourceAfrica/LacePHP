<?php
namespace Weave\Plugins\ShoeAI\Agents;

class ShoeGenie
{
    public static function scaffold(?string $prompt): void
    {
        $cfg = config()['ai'] ?? [];
        if (empty($cfg['enabled'])) {
            fwrite(STDERR, "❌ AI disabled in config\n");
            exit(1);
        }

        if (! $prompt) {
            fwrite(STDOUT, "🗣️  Describe the API you want:\n> ");
            $prompt = trim(fgets(STDIN));
        }

        $client = new HttpClient();
        $resp   = $client->post('/scaffold.php', ['prompt'=>$prompt]);
        if ($resp['status'] !== 200) {
            fwrite(STDERR, "❌ Scaffold failed: {$resp['body']}\n");
            exit(1);
        }

        $json = json_decode($resp['body'], true);
        if (! is_array($json)) {
            fwrite(STDERR, "❌ Invalid JSON:\n{$resp['body']}\n");
            exit(1);
        }

        foreach ($json as $relPath => $code) {
            $full = dirname(__DIR__,3) . '/' . $relPath;
            @mkdir(dirname($full), 0755, true);
            file_put_contents($full, $code);
            fwrite(STDOUT, "📝 Wrote {$relPath}\n");
        }

        fwrite(STDOUT, "🎉 Scaffold complete.\n");
    }
}