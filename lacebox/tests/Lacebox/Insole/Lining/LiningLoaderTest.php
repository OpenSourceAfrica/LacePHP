<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Insole\Lining;

use LacePHP\Lacebox\Tests\TestCase;

final class LiningLoaderTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Insole\Lining\LiningLoader') || interface_exists('Lacebox\Insole\Lining\LiningLoader') || trait_exists('Lacebox\Insole\Lining\LiningLoader'), 'Type not found: Lacebox\Insole\Lining\LiningLoader');

        if (class_exists('Lacebox\Insole\Lining\LiningLoader')) {
            $ref = new \ReflectionClass('Lacebox\Insole\Lining\LiningLoader');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("load"), "Missing method load");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Insole\Lining\LiningLoader', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Insole\Lining\LiningLoader') || trait_exists('Lacebox\Insole\Lining\LiningLoader'));
        }
    }
}
