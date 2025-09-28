<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole\Cobble;

use LacePHP\Lacebox\Tests\TestCase;

final class BlueprintTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Sole\Cobble\Blueprint') || interface_exists('Lacebox\Sole\Cobble\Blueprint') || trait_exists('Lacebox\Sole\Cobble\Blueprint'), 'Type not found: Lacebox\Sole\Cobble\Blueprint');

        if (class_exists('Lacebox\Sole\Cobble\Blueprint')) {
            $ref = new \ReflectionClass('Lacebox\Sole\Cobble\Blueprint');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("bigInteger"), "Missing method bigInteger");
            $this->assertTrue($ref->hasMethod("boolean"), "Missing method boolean");
            $this->assertTrue($ref->hasMethod("date"), "Missing method date");
            $this->assertTrue($ref->hasMethod("dateTime"), "Missing method dateTime");
            $this->assertTrue($ref->hasMethod("decimal"), "Missing method decimal");
            $this->assertTrue($ref->hasMethod("default"), "Missing method default");
            $this->assertTrue($ref->hasMethod("defaultRaw"), "Missing method defaultRaw");
            $this->assertTrue($ref->hasMethod("double"), "Missing method double");
            $this->assertTrue($ref->hasMethod("dropColumn"), "Missing method dropColumn");
            $this->assertTrue($ref->hasMethod("enum"), "Missing method enum");
            $this->assertTrue($ref->hasMethod("float"), "Missing method float");
            $this->assertTrue($ref->hasMethod("increments"), "Missing method increments");
            $this->assertTrue($ref->hasMethod("index"), "Missing method index");
            $this->assertTrue($ref->hasMethod("integer"), "Missing method integer");
            $this->assertTrue($ref->hasMethod("json"), "Missing method json");
            $this->assertTrue($ref->hasMethod("longText"), "Missing method longText");
            $this->assertTrue($ref->hasMethod("mediumText"), "Missing method mediumText");
            $this->assertTrue($ref->hasMethod("nullable"), "Missing method nullable");
            $this->assertTrue($ref->hasMethod("precision"), "Missing method precision");
            $this->assertTrue($ref->hasMethod("primary"), "Missing method primary");
            $this->assertTrue($ref->hasMethod("renameColumn"), "Missing method renameColumn");
            $this->assertTrue($ref->hasMethod("scale"), "Missing method scale");
            $this->assertTrue($ref->hasMethod("smallInteger"), "Missing method smallInteger");
            $this->assertTrue($ref->hasMethod("softDeletes"), "Missing method softDeletes");
            $this->assertTrue($ref->hasMethod("string"), "Missing method string");
            $this->assertTrue($ref->hasMethod("text"), "Missing method text");
            $this->assertTrue($ref->hasMethod("time"), "Missing method time");
            $this->assertTrue($ref->hasMethod("timestamp"), "Missing method timestamp");
            $this->assertTrue($ref->hasMethod("timestamps"), "Missing method timestamps");
            $this->assertTrue($ref->hasMethod("tinyInteger"), "Missing method tinyInteger");
            $this->assertTrue($ref->hasMethod("unique"), "Missing method unique");
            $this->assertTrue($ref->hasMethod("uniqueIndex"), "Missing method uniqueIndex");
            $this->assertTrue($ref->hasMethod("unsigned"), "Missing method unsigned");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Sole\Cobble\Blueprint', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Sole\Cobble\Blueprint') || trait_exists('Lacebox\Sole\Cobble\Blueprint'));
        }
    }
}
