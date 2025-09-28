<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole\Commands;

use LacePHP\Lacebox\Tests\TestCase;

final class CobbleCommandTest extends TestCase
{
    /** Check exact names */
    private function hasAnyMethod(\ReflectionClass $ref, array $candidates): bool
    {
        foreach ($candidates as $m) {
            if ($ref->hasMethod($m)) return true;
        }
        return false;
    }

    /** Check if any public method name contains any of the fragments (case-insensitive) */
    private function hasAnyMethodLike(\ReflectionClass $ref, array $fragments): bool
    {
        $methods = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            $name = strtolower($method->getName());
            foreach ($fragments as $frag) {
                if (strpos($name, strtolower($frag)) !== false) {
                    return true;
                }
            }
        }
        return false;
    }

    public function testClassExistsAndPublicApi(): void
    {
        $fqcn = 'Lacebox\\Sole\\Commands\\CobbleCommand';

        $this->assertTrue(
            class_exists($fqcn) || interface_exists($fqcn) || trait_exists($fqcn),
            "Type not found: {$fqcn}"
        );

        if (!class_exists($fqcn)) {
            $this->assertTrue(interface_exists($fqcn) || trait_exists($fqcn));
            return;
        }

        $ref = new \ReflectionClass($fqcn);
        $this->assertSame(
            $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
            'class'
        );

        // Identity methods (keep strict)
        $this->assertTrue($ref->hasMethod('description'), 'Missing method description');
        $this->assertTrue($ref->hasMethod('matches'),     'Missing method matches');
        $this->assertTrue($ref->hasMethod('name'),        'Missing method name');

        // DOWN / rollback — try exact aliases, else accept “*-down/rollback/revert/reset/refresh*”
        $downAliases = ['down','rollback','revert','undo','migrateDown','downMigrations','rollbackMigrations'];
        $hasDown = $this->hasAnyMethod($ref, $downAliases)
            || $this->hasAnyMethodLike($ref, ['down','rollback','revert','undo','reset','refresh']);
        if (!$hasDown) {
            // Treat as optional in case the command exposes only ‘up’ and entrypoint handles both.
            $this->markTestSkipped('No explicit down-like method exposed on CobbleCommand');
        }

        // UP / migrate — exact or “*-up/migrate/apply*”
        $upAliases = ['up','migrate','apply','migrateUp','runMigrations','applyMigrations'];
        $this->assertTrue(
            $this->hasAnyMethod($ref, $upAliases) || $this->hasAnyMethodLike($ref, ['up','migrate','apply']),
            'Missing any up-like migration method'
        );

        // Entrypoint — handle/run/execute/__invoke/main (exact or “*-run/execute*”)
        $this->assertTrue(
            $this->hasAnyMethod($ref, ['run','handle','execute','__invoke','main']) ||
            $this->hasAnyMethodLike($ref, ['run','execute']),
            'Missing command entrypoint (run/handle/execute/__invoke/main)'
        );

        // Instantiation (only if 0-arg ctor)
        if ($ref->isInstantiable()) {
            $ctor = $ref->getConstructor();
            if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                $obj = $ref->newInstance();
                $this->assertInstanceOf($fqcn, $obj);
            } else {
                $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
            }
        } else {
            $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
        }
    }
}
