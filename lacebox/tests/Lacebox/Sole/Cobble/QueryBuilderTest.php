<?php
declare(strict_types=1);
namespace Lacebox\tests\Lacebox\Sole\Cobble;

use LacePHP\Lacebox\Tests\TestCase;

final class QueryBuilderTest extends TestCase {
    public function testClassExistsAndPublicApi(): void {
        $this->assertTrue(class_exists('Lacebox\Sole\Cobble\QueryBuilder') || interface_exists('Lacebox\Sole\Cobble\QueryBuilder') || trait_exists('Lacebox\Sole\Cobble\QueryBuilder'), 'Type not found: Lacebox\Sole\Cobble\QueryBuilder');

        if (class_exists('Lacebox\Sole\Cobble\QueryBuilder')) {
            $ref = new \ReflectionClass('Lacebox\Sole\Cobble\QueryBuilder');
            $this->assertSame(
                $ref->isInterface() ? 'interface' : ($ref->isTrait() ? 'trait' : 'class'),
                'class'
            );

            $this->assertTrue($ref->hasMethod("asClass"), "Missing method asClass");
            $this->assertTrue($ref->hasMethod("count"), "Missing method count");
            $this->assertTrue($ref->hasMethod("delete"), "Missing method delete");
            $this->assertTrue($ref->hasMethod("first"), "Missing method first");
            $this->assertTrue($ref->hasMethod("forPage"), "Missing method forPage");
            $this->assertTrue($ref->hasMethod("get"), "Missing method get");
            $this->assertTrue($ref->hasMethod("insert"), "Missing method insert");
            $this->assertTrue($ref->hasMethod("insertGetId"), "Missing method insertGetId");
            $this->assertTrue($ref->hasMethod("join"), "Missing method join");
            $this->assertTrue($ref->hasMethod("leftJoin"), "Missing method leftJoin");
            $this->assertTrue($ref->hasMethod("limit"), "Missing method limit");
            $this->assertTrue($ref->hasMethod("offset"), "Missing method offset");
            $this->assertTrue($ref->hasMethod("orWhere"), "Missing method orWhere");
            $this->assertTrue($ref->hasMethod("orWhereBetween"), "Missing method orWhereBetween");
            $this->assertTrue($ref->hasMethod("orWhereIn"), "Missing method orWhereIn");
            $this->assertTrue($ref->hasMethod("orWhereNotBetween"), "Missing method orWhereNotBetween");
            $this->assertTrue($ref->hasMethod("orWhereNotIn"), "Missing method orWhereNotIn");
            $this->assertTrue($ref->hasMethod("orderBy"), "Missing method orderBy");
            $this->assertTrue($ref->hasMethod("paginate"), "Missing method paginate");
            $this->assertTrue($ref->hasMethod("rightJoin"), "Missing method rightJoin");
            $this->assertTrue($ref->hasMethod("select"), "Missing method select");
            $this->assertTrue($ref->hasMethod("selectRaw"), "Missing method selectRaw");
            $this->assertTrue($ref->hasMethod("table"), "Missing method table");
            $this->assertTrue($ref->hasMethod("update"), "Missing method update");
            $this->assertTrue($ref->hasMethod("value"), "Missing method value");
            $this->assertTrue($ref->hasMethod("where"), "Missing method where");
            $this->assertTrue($ref->hasMethod("whereBetween"), "Missing method whereBetween");
            $this->assertTrue($ref->hasMethod("whereIn"), "Missing method whereIn");
            $this->assertTrue($ref->hasMethod("whereNotBetween"), "Missing method whereNotBetween");
            $this->assertTrue($ref->hasMethod("whereNotIn"), "Missing method whereNotIn");
            $this->assertTrue($ref->hasMethod("whereRaw"), "Missing method whereRaw");
            $this->assertTrue($ref->hasMethod("with"), "Missing method with");

            if ($ref->isInstantiable()) {
                $ctor = $ref->getConstructor();
                if (!$ctor || $ctor->getNumberOfRequiredParameters() === 0) {
                    $obj = $ref->newInstance();
                    $this->assertInstanceOf('Lacebox\Sole\Cobble\QueryBuilder', $obj);
                } else {
                    $this->markTestSkipped('Constructor requires arguments; instantiation skipped.');
                }
            } else {
                $this->markTestSkipped('Not instantiable (abstract/interface/trait).');
            }
        } else {
            $this->assertTrue(interface_exists('Lacebox\Sole\Cobble\QueryBuilder') || trait_exists('Lacebox\Sole\Cobble\QueryBuilder'));
        }
    }
}
