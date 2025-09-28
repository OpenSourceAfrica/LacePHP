<?php
declare(strict_types=1);
namespace LacePHP\Lacebox\Tests;

use PHPUnit\Framework\TestCase as Base;

abstract class TestCase extends Base {
    protected function assertArrayHasSubset(array $subset, array $array, string $message = ''): void {
        foreach ($subset as $k => $v) {
            $this->assertArrayHasKey($k, $array, $message ?: "Missing key $k");
            $this->assertSame($v, $array[$k], $message ?: "Mismatched value at $k");
        }
    }

    protected function skipUnlessExtensions(array $exts, string $why): void {
        foreach ($exts as $e) {
            if (extension_loaded($e)) return;
        }
        $this->markTestSkipped($why);
    }

    protected function assertHasAnyMethod(\ReflectionClass $ref, array $candidates, string $label): void {
        foreach ($candidates as $m) {
            if ($ref->hasMethod($m)) return;
        }
        $this->fail("Missing any of methods for {$label}: " . implode(', ', $candidates));
    }
}
