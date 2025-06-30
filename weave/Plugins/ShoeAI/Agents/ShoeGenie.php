<?php
namespace Weave\Plugins\ShoeAI\Agents;

class ShoeGenie
{
    private const MANIFEST = __DIR__ . '/../scaffold-manifest.json';

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

        $manifest = [];

        foreach ($json as $relPath => $code) {
            $full = dirname(__DIR__,3) . '/' . $relPath;
            @mkdir(dirname($full), 0755, true);

            // record old contents (or null if file did not exist)
            if (file_exists($full)) {
                $manifest[$relPath] = file_get_contents($full);
            } else {
                $manifest[$relPath] = null;
            }

            file_put_contents($full, $code);
            fwrite(STDOUT, "📝 Wrote {$relPath}\n");
        }


        // save the manifest so we can undo later
        file_put_contents(self::MANIFEST, json_encode($manifest, JSON_PRETTY_PRINT));

        fwrite(STDOUT, "🎉 Scaffold complete.\n");
        fwrite(STDOUT, "ℹ️  To undo, run: php lace ai:rollback\n");
    }

    /**
     * Roll back the last scaffold: delete new files, restore overwritten ones.
     */
    public static function rollback(): void
    {
        if (! file_exists(self::MANIFEST)) {
            fwrite(STDERR, "❌ No scaffold manifest found; nothing to roll back.\n");
            exit(1);
        }

        $manifest = json_decode(file_get_contents(self::MANIFEST), true);
        foreach ($manifest as $relPath => $oldContent) {
            $full = dirname(__DIR__, 3) . '/' . $relPath;

            if ($oldContent === null) {
                // file was newly created — remove it
                if (file_exists($full)) {
                    unlink($full);
                    fwrite(STDOUT, "🗑 Deleted new file: {$relPath}\n");
                }
            } else {
                // file existed before — restore previous content
                file_put_contents($full, $oldContent);
                fwrite(STDOUT, "♻️  Restored file: {$relPath}\n");
            }
        }

        // remove the manifest so you can scaffold fresh next time
        unlink(self::MANIFEST);
        fwrite(STDOUT, "✅ Rollback complete.\n");
    }
}