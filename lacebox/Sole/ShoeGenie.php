<?php
namespace Lacebox\Sole;

use OpenAI\Client as OpenAI;

class ShoeGenie
{
    protected static $config;
    protected static $usageFile = __DIR__.'/../../shoebox/cache/ai_usage.json';

    protected static function loadConfig(): array
    {
        if (self::$config === null) {
            $aiConfig = config()['ai'] ?? [];
            self::$config = is_array($aiConfig) ? $aiConfig : [];
        }
        return self::$config;
    }

    /** Show current quota & subscription info */
    public static function status(): void
    {
        $ai   = self::loadConfig();
        $file = self::$usageFile;
        $used = file_exists($file)
            ? json_decode(file_get_contents($file), true)['used'] ?? 0
            : 0;

        $quota = $ai['free_quota'] ?? 0;
        $tier  = $ai['subscription']['tier'] ?? 'free';
        echo "\n🔮 ShoeGenie AI Mode\n";
        echo "  Tier:       {$tier}\n";
        echo "  Model:      {$ai['model']}\n";
        echo "  Used:       {$used}/{$quota} tokens\n";
        echo "  Reset At:   {$ai['subscription']['expires_at']}\n\n";
    }

    /** Main scaffolding entry */
    public static function scaffold(?string $prompt): void
    {
        $ai = self::loadConfig();
        if (! $ai['enabled']) {
            fwrite(STDERR, "❌ AI mode is disabled in lace.json\n");
            exit(1);
        }
        if (! $prompt) {
            echo "\n🗣️  Describe the API you want LacePHP to generate:\n> ";
            $prompt = trim(fgets(STDIN));
        }
        $key = env('OPENAI_KEY', null);
        if (! $key) {
            fwrite(STDERR, "❌ No OPENAI_KEY found in your .env. Generate one or switch tiers.\n");
            exit(1);
        }

        // 1) Check & consume quota
        $needed = 1; // simple unit count per scaffold
        if (! self::consumeQuota($needed)) {
            fwrite(STDERR, "❌ Free quota exhausted. Please upgrade to Pro tier.\n");
            exit(1);
        }

        // 2) Build prompt template
        $tpl = file_get_contents(__DIR__.'/Prompts/scaffold_api.tpl');
        $tpl = str_replace('{{description}}', $prompt, $tpl);

        // 3) Call OpenAI
        $client = OpenAI::factory(['api_key'=>$key]);
        $res = $client->chat()->create([
            'model'    => $ai['model'],
            'messages' => [['role'=>'system','content'=>$tpl]]
        ]);

        $body = $res['choices'][0]['message']['content'];
        $json = json_decode($body, true);
        if (! is_array($json)) {
            fwrite(STDERR, "❌ ShoeGenie returned invalid JSON. Raw output:\n$body\n");
            exit(1);
        }

        // 4) Write each section
        foreach ($json as $section => $code) {
            self::writeSection($section, $code);
        }

        echo "\n🎉 Scaffold complete! Quota remaining: "
            . (self::remainingQuota()) ."/{$ai['free_quota']}\n";
    }

    protected static function writeSection(string $section, string $code): void
    {
        switch ($section) {
            case 'migration':
                $file = getcwd().'/shoebox/migrations/'.time().'_create_api.php';
                break;
            case 'model':
                $file = getcwd().'/weave/Models/'.ucfirst($section).'.php';
                break;
            case 'controller':
                $file = getcwd().'/weave/Controllers/'.ucfirst($section).'Controller.php';
                break;
            case 'routes':
                $file = getcwd().'/routes/api.php';
                $code = "\n// — ShoeGenie routes —\n".$code;
                break;
            case 'test':
                $file = getcwd().'/weave/Heel/Tests/'.ucfirst($section).'Test.php';
                break;
            default:
                return;
        }
        file_put_contents($file, $code.PHP_EOL, FILE_APPEND);
        echo "  ✓ {$section} → {$file}\n";
    }

    /** Consume quota; return false if not enough left */
    protected static function consumeQuota(int $n): bool
    {
        $file = self::$usageFile;
        $data = file_exists($file)
            ? json_decode(file_get_contents($file), true)
            : ['used'=>0,'last_reset'=>date('c')];

        $ai    = self::loadConfig();
        $quota = $ai['free_quota'] ?? 0;
        if ($data['used'] + $n > $quota) {
            return false;
        }
        $data['used'] += $n;
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
        return true;
    }

    protected static function remainingQuota(): int
    {
        $file = self::$usageFile;
        $used = file_exists($file)
            ? json_decode(file_get_contents($file), true)['used'] ?? 0
            : 0;
        return max(0, (self::loadConfig()['free_quota'] ?? 0) - $used);
    }
}