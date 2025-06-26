<?php
namespace Lacebox\Sole;

use OpenAI\Client as OpenAI;

class ShoeBuddy
{
    // where we store Q→A cache
    protected static $cacheFile = __DIR__.'/../../shoebox/cache/buddy_cache.json';
    // where we track monthly usage counts
    protected static $usageFile = __DIR__.'/../../shoebox/cache/buddy_usage.json';
    protected static $config;

    /** Load & memoize ai config */
    protected static function loadConfig(): array
    {
        if (self::$config === null) {
            $ai = config()['ai'] ?? [];
            // require premium tier
            if (($ai['subscription']['tier'] ?? '') !== 'pro') {
                fwrite(STDERR, "❌ ShoeBuddy is a premium feature. Upgrade your AI tier to ‘pro’ in lace.json.\n");
                exit(1);
            }
            self::$config = $ai;
        }
        return self::$config;
    }

    /** Primary entry: ask a question, returns AI’s answer */
    public static function ask(string $file, int $line, string $question): void
    {
        $ai = self::loadConfig();

        // 1) enforce rate limit
        $used = self::readUsage();
        $limit = $ai['subscription']['premium_quota'] ?? 100; // e.g. 100/month
        if ($used >= $limit) {
            fwrite(STDERR, "❌ ShoeBuddy monthly quota ({$limit}) exhausted.\n");
            exit(1);
        }

        // 2) build the prompt context
        $snippet = self::grabSnippet($file, $line, 5);
        $system  = "You are ShoeBuddy, the LacePHP expert.  Here's the relevant code snippet:\n\n"
            . $snippet
            . "\n\nQUESTION: {$question}\n\nAnswer as if you know LacePHP's conventions.";

        // 3) check cache
        $key = md5($system);
        $cache = self::readCache();
        if (isset($cache[$key])) {
            echo $cache[$key] . "\n";
            return;
        }

        // 4) call OpenAI
        $client = OpenAI::factory(['api_key'=> env('OPENAI_KEY')]);
        $res = $client->chat()->create([
            'model'    => $ai['model'] ?? 'gpt-4',
            'messages' => [
                ['role'=>'system','content'=>$system]
            ],
            'max_tokens' => 500
        ]);
        $answer = trim($res['choices'][0]['message']['content']);

        // 5) cache & increment usage
        $cache[$key] = $answer;
        file_put_contents(self::$cacheFile, json_encode($cache, JSON_PRETTY_PRINT));

        self::writeUsage($used + 1);

        // 6) output
        echo $answer . "\n";
    }

    /** Grab a few lines around the error line for context */
    protected static function grabSnippet($file, $line, $radius): string
    {
        if (!file_exists($file)) {
            return "// Unable to open file {$file}";
        }
        $lines = file($file);
        $start = max(0, $line - $radius - 1);
        $end   = min(count($lines)-1, $line + $radius - 1);
        $snip  = '';
        for ($i = $start; $i <= $end; $i++) {
            $snip .= sprintf("%4d | %s", $i+1, $lines[$i]);
        }
        return $snip;
    }

    protected static function readCache(): array
    {
        if (!file_exists(self::$cacheFile)) {
            return [];
        }
        return json_decode(file_get_contents(self::$cacheFile), true) ?: [];
    }

    protected static function readUsage(): int
    {
        if (!file_exists(self::$usageFile)) {
            return 0;
        }
        $u = json_decode(file_get_contents(self::$usageFile), true);
        return (int)($u['used'] ?? 0);
    }

    protected static function writeUsage(int $n): void
    {
        $data = ['used'=>$n, 'reset_at'=> date('Y-m-01') ];
        file_put_contents(self::$usageFile, json_encode($data, JSON_PRETTY_PRINT));
    }
}