<?php
namespace Weave\Plugins\ShoeAI\Agents;

class ShoeBuddy
{
    public static function ask(string $file, int $line, string $q): void
    {
        $cfg = config()['ai'] ?? [];
        if (empty($cfg['enabled'])) {
            fwrite(STDERR, "AI disabled in config\n");
            exit(1);
        }

        $client = new HttpClient();
        $resp   = $client->post('/buddy.php', [
            'file'     => $file,
            'line'     => $line,
            'question' => $q,
            'hwid'     => lace_hwid(),
        ]);

        if ($resp['status'] !== 200) {
            fwrite(STDERR, "Buddy failed: {$resp['body']}\n");
            exit(1);
        }

        echo $resp['body'], "\n";
    }
}