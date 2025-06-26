<?php
namespace Lacebox\Tongue\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS|Attribute::TARGET_METHOD|Attribute::IS_REPEATABLE)]
class GraphQLField
{
    public string   $name;
    public mixed    $type;
    public callable $resolver;

    public function __construct(string $name, mixed $type, callable $resolver)
    {
        $this->name     = $name;
        $this->type     = $type;
        $this->resolver = $resolver;
    }
}
