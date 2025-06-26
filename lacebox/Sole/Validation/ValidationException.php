<?php
namespace Lacebox\Sole\Validation;

use Throwable;

class ValidationException extends \Exception
{
    protected $errors;

    public function __construct(array $errors)
    {
        parent::__construct("Validation failed");
        $this->errors = $errors;
    }

    public function errors(): array
    {
        return $this->errors;
    }
}