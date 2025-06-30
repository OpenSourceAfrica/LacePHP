<?php
namespace Lacebox\Sole;

use Lacebox\Insole\Stitching\SingletonTrait;
use Lacebox\Sole\Http\Request;
use Lacebox\Shoelace\RuleInterface;
use Lacebox\Sole\Validation\Rules\EmailRule;
use Lacebox\Sole\Validation\Rules\RequiredRule;
use Lacebox\Sole\Validation\ValidationException;

class RequestValidator
{

    use SingletonTrait;

    /** @var self|null */
    private static $instance = null;

    /** field => RuleInterface[] */
    private $spec = [];

    /** custom name => RuleInterface */
    private $custom = [];

    /** stop each field on first failure? */
    private $firstErrorMode = false;

    /** if true, throws instead of echo+exit */
    private $throwOnFail = false;

    private $errors = [];

    /**
     * Enable “first‐error” mode (default: off).
     * Shoe-themed name for Laravel’s “bail”.
     */
    public function lace_break(bool $on = true): self
    {
        $this->firstErrorMode = $on;
        return $this;
    }

    /**
     * If true, validate() will throw a ValidationException instead
     * of directly echoing JSON+exit.
     */
    public function throwOnFail(bool $on = true): self
    {
        $this->throwOnFail = $on;
        return $this;
    }

    /**
     * Register custom rule instances from weave/ValidationRules.
     *
     *   ['isEven'=> new \Weave\ValidationRules\IsEvenRule()]
     */
    public function setCustomRules(array $map): self
    {
        $this->custom = $map;
        return $this;
    }

    /**
     * Define your field rules:
     *
     *   'age'      => 'required,isEven,min[18]',
     *   'email'    => 'required,email',
     *   'username' => ['required','max[32]']
     */
    public function setRules(array $rules): self
    {
        foreach ($rules as $field => $cfg) {
            $entries = is_array($cfg) ? $cfg : explode(',', $cfg);
            $objs     = [];

            foreach ($entries as $entry) {
                $entry = trim($entry);
                // custom rule?
                if (strpos($entry, 'custom:') === 0) {
                    $name = substr($entry, 7);
                    if (! isset($this->custom[$name])) {
                        throw new \RuntimeException("Unknown custom rule: {$name}");
                    }
                    $objs[] = $this->custom[$name];
                    continue;
                }

                // bracket-parameter syntax: name[param]
                if (preg_match('/^(\w+)\[(.+)\]$/', $entry, $m)) {
                    list(, $rule, $param) = $m;
                    $class = 'Lacebox\\Sole\\Validation\\Rules\\' . ucfirst($rule) . 'Rule';
                    if (! class_exists($class)) {
                        throw new \RuntimeException("Unknown rule: {$rule}");
                    }
                    $objs[] = new $class($param);
                    continue;
                }

                // no-param built-in
                switch ($entry) {
                    case 'required':
                        $objs[] = new RequiredRule();
                        break;
                    case 'email':
                        $objs[] = new EmailRule();
                        break;
                    default:
                        throw new \RuntimeException("Unknown rule: {$entry}");
                }
            }

            $this->spec[$field] = $objs;
        }

        return $this;
    }

    /**
     * Run the validation, collecting $this->errors.
     * Returns true on success, false on failure.
     * If throwOnFail=true, will throw ValidationException.
     */
    public function validate(): bool
    {
        $this->errors = [];
        $data = Request::grab()->all();

        foreach ($this->spec as $field => $rules) {
            $value = $data[$field] ?? null;

            foreach ($rules as $ruleObj) {
                /** @var RuleInterface $ruleObj */
                if (! $ruleObj->validate($value, $data)) {
                    $this->errors[$field][] = $ruleObj->message();
                    if ($this->firstErrorMode) {
                        break;  // stop this field’s rules
                    }
                }
            }
        }

        if (empty($this->errors)) {
            return true;
        }

        if ($this->throwOnFail) {
            throw new ValidationException($this->errors);
        }

        // default: immediate JSON response + exit
        $resp = ShoeResponder::getInstance();
        echo $resp->json(['errors' => $this->errors], 422);
        exit;
    }

    /** true if last validate() found errors */
    public function fails(): bool
    {
        return ! empty($this->errors);
    }

    /** get the raw errors array */
    public function errors(): array
    {
        return $this->errors;
    }

    /** get the first error message for a given field, or null */
    public function first(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }
}