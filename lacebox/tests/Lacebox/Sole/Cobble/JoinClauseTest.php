<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole\Cobble;

use LacePHP\Lacebox\Tests\TestCase;

final class JoinClauseTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Sole\Cobble\JoinClause') || interface_exists('Lacebox\Sole\Cobble\JoinClause') || trait_exists('Lacebox\Sole\Cobble\JoinClause'), 'Type not found: Lacebox\Sole\Cobble\JoinClause');

        if (class_exists('Lacebox\Sole\Cobble\JoinClause')) {
            $ref = new \ReflectionClass('Lacebox\Sole\Cobble\JoinClause');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("on"), "Missing method on");
            $this->assertTrue($ref->hasMethod("onRaw"), "Missing method onRaw");
            $this->assertTrue($ref->hasMethod("orOn"), "Missing method orOn");
            $this->assertTrue($ref->hasMethod("toSqlAndBindings"), "Missing method toSqlAndBindings");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Sole\Cobble\JoinClause', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Sole\Cobble\JoinClause') || trait_exists('Lacebox\Sole\Cobble\JoinClause'));
        }
    }
}
