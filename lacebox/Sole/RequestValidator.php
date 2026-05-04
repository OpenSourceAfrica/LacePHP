<?php

namespace Lacebox\Sole;

use Lacebox\Insole\Stitching\SingletonTrait;
use Lacebox\Shoelace\RuleInterface;
use Lacebox\Sole\Http\ShoeRequest;
use Lacebox\Sole\Validation\ValidationException;

// Built-in rules
use Lacebox\Sole\Validation\Rules\RequiredRule;
use Lacebox\Sole\Validation\Rules\NullableRule;
use Lacebox\Sole\Validation\Rules\FilledRule;

use Lacebox\Sole\Validation\Rules\StringRule;
use Lacebox\Sole\Validation\Rules\BooleanRule;
use Lacebox\Sole\Validation\Rules\NumericRule;
use Lacebox\Sole\Validation\Rules\IntegerRule;

use Lacebox\Sole\Validation\Rules\UrlRule;
use Lacebox\Sole\Validation\Rules\UuidRule;

use Lacebox\Sole\Validation\Rules\DateRule;
use Lacebox\Sole\Validation\Rules\AfterRule;
use Lacebox\Sole\Validation\Rules\BeforeRule;

use Lacebox\Sole\Validation\Rules\SameRule;
use Lacebox\Sole\Validation\Rules\DifferentRule;
use Lacebox\Sole\Validation\Rules\ConfirmedRule;

use Lacebox\Sole\Validation\Rules\MaxRule;
use Lacebox\Sole\Validation\Rules\MinRule;

class RequestValidator
{
    use SingletonTrait;

    /** fieldPattern => RuleInterface[] */
    private $spec = [];

    /** custom name => RuleInterface */
    private $custom = [];

    private $firstErrorMode = false;
    private $throwOnFail = false;

    private $errors = [];

    public function lace_break(bool $on = true): self
    {
        $this->firstErrorMode = $on;
        return $this;
    }

    public function throwOnFail(bool $on = true): self
    {
        $this->throwOnFail = $on;
        return $this;
    }

    public function setCustomRules(array $map): self
    {
        $this->custom = $map;
        return $this;
    }

    /**
     * Define your rules:
     * [
     *   'fname'            => 'required,max[150]',
     *   'email'            => 'required,email',
     *   'status'           => 'required,in:enabled,disabled',
     *   'role'             => 'in[admin,staff]',
     *   'answers'          => 'required,array',
     *   'answers.*.id'     => 'required',
     *   'answers.*.answer' => 'required,max[150]',
     * ]
     */
    public function setRules(array $rules): self
    {
        $this->spec = [];

        foreach ($rules as $field => $cfg) {
            $entries = $this->normaliseRuleEntries($cfg);

            $objs = [];
            foreach ($entries as $entry) {
                $entry = trim((string)$entry);
                if ($entry === '') continue;

                $objs[] = $this->parseRuleEntry($entry, (string)$field);
            }

            $this->spec[(string)$field] = $objs;
        }

        return $this;
    }

    /**
     * Convert a rule config into an array of rule strings,
     * while preserving commas inside brackets:
     *   "between[1,10],required" => ["between[1,10]","required"]
     */
    private function normaliseRuleEntries($cfg): array
    {
        if (is_array($cfg)) {
            return array_values($cfg);
        }

        $s = trim((string)$cfg);
        if ($s === '') return [];

        return $this->splitRulesPreserveBrackets($s);
    }


    /**
     * Split by commas, but DO NOT split inside [...].
     * Also respects escape sequences like \, \[ \]
     */
    private function splitRulesPreserveBrackets(string $s): array
    {
        $out = [];
        $buf = '';
        $depth = 0;

        $len = strlen($s);
        for ($i = 0; $i < $len; $i++) {
            $ch = $s[$i];

            // Preserve escape sequences as literal: "\X"
            if ($ch === '\\' && $i + 1 < $len) {
                $buf .= $ch . $s[$i + 1];
                $i++;
                continue;
            }

            if ($ch === '[') {
                $depth++;
                $buf .= $ch;
                continue;
            }

            if ($ch === ']') {
                if ($depth > 0) $depth--;
                $buf .= $ch;
                continue;
            }

            // Split only at top-level
            if ($ch === ',' && $depth === 0) {
                $part = trim($buf);
                if ($part !== '') $out[] = $part;
                $buf = '';
                continue;
            }

            $buf .= $ch;
        }

        $last = trim($buf);
        if ($last !== '') $out[] = $last;

        return $out;
    }

    private function parseRuleEntry(string $entry, string $fieldPattern): RuleInterface
    {
        // Custom rule: custom:name
        if (strpos($entry, 'custom:') === 0) {
            $name = trim(substr($entry, 7));
            if ($name === '' || !isset($this->custom[$name])) {
                throw new \RuntimeException("Unknown custom rule: {$name}");
            }
            $rule = $this->custom[$name];
            if (method_exists($rule, 'setField')) $rule->setField($fieldPattern);
            return $rule;
        }

        // --- REGEX special-case ---
        // Supports:
        //   regex:/^...$/
        //   regex[/^...$/]
        //   regex[^[a-z]+$]  (no delimiters -> RegexRule wraps it)
        if (stripos($entry, 'regex:') === 0) {
            $pattern = trim(substr($entry, 6));
            $r = new \Lacebox\Sole\Validation\Rules\RegexRule($pattern);
            if (method_exists($r, 'setField')) $r->setField($fieldPattern);
            return $r;
        }

        if (preg_match('/^regex\[(.*)\]$/is', $entry, $m)) {
            $pattern = trim((string)$m[1]);
            $r = new \Lacebox\Sole\Validation\Rules\RegexRule($pattern);
            if (method_exists($r, 'setField')) $r->setField($fieldPattern);
            return $r;
        }

        // Colon rules (already agreed): in: a|b|c, notin: a|b|c, exists: t|c, unique: t|c|ignore|ignoreCol
        if (strpos($entry, ':') !== false) {
            [$ruleName, $argStr] = explode(':', $entry, 2);
            $ruleName = strtolower(trim($ruleName));
            $argStr   = trim((string)$argStr);

            $parts = [];
            if ($argStr !== '') {
                $sep = (strpos($argStr, '|') !== false) ? '|' : ',';
                $parts = array_map('trim', explode($sep, $argStr));
                $parts = array_values(array_filter($parts, 'strlen'));
            }

            switch ($ruleName) {
                case 'in':
                    $rule = new \Lacebox\Sole\Validation\Rules\InRule($parts);
                    break;
                case 'notin':
                case 'not_in':
                    $rule = new \Lacebox\Sole\Validation\Rules\NotInRule($parts);
                    break;
                case 'exists':
                    $table  = $parts[0] ?? '';
                    $column = $parts[1] ?? null;
                    $rule = new \Lacebox\Sole\Validation\Rules\ExistsRule($table, $column);
                    break;
                case 'unique':
                    $table       = $parts[0] ?? '';
                    $column      = $parts[1] ?? null;
                    $ignoreValue = $parts[2] ?? null;
                    $ignoreCol   = $parts[3] ?? null;
                    $rule = new \Lacebox\Sole\Validation\Rules\UniqueRule($table, $column, $ignoreValue, $ignoreCol);
                    break;
                default:
                    throw new \RuntimeException("Unknown rule: {$ruleName}");
            }

            if (method_exists($rule, 'setField')) $rule->setField($fieldPattern);
            return $rule;
        }

        // Simple rules
        $simple = strtolower(trim($entry));

        $map = [
            'required'  => RequiredRule::class,
            'nullable'  => NullableRule::class,
            'filled'    => FilledRule::class,

            'string'    => StringRule::class,
            'boolean'   => BooleanRule::class,
            'numeric'   => NumericRule::class,
            'integer'   => IntegerRule::class,

            'url'       => UrlRule::class,
            'uuid'      => UuidRule::class,

            'date'      => DateRule::class,

            'same'      => SameRule::class,       // needs param (handled below too)
            'different' => DifferentRule::class,  // needs param (handled below too)
            'after'     => AfterRule::class,      // needs param
            'before'    => BeforeRule::class,     // needs param
            'confirmed' => ConfirmedRule::class,
        ];

        // Parameterised bracket rules: max[150], after[field], same[field], etc.
        if (preg_match('/^([a-zA-Z_]\w*)(?:\[(.*)\])?$/', $entry, $m)) {
            $ruleName = strtolower($m[1]);
            $paramStr = isset($m[2]) ? trim((string)$m[2]) : null;

            switch ($ruleName) {
                case 'max':       $r = new MaxRule((string)$paramStr); break;
                case 'min':       $r = new MinRule((string)$paramStr); break;

                case 'same':      $r = new SameRule((string)$paramStr); break;
                case 'different': $r = new DifferentRule((string)$paramStr); break;

                case 'after':     $r = new AfterRule((string)$paramStr); break;
                case 'before':    $r = new BeforeRule((string)$paramStr); break;

                default:
                    if (isset($map[$ruleName])) {
                        $class = $map[$ruleName];
                        // If class expects a param, it can accept null safely.
                        $r = ($paramStr !== null && $paramStr !== '')
                            ? new $class($paramStr)
                            : new $class();
                        break;
                    }

                    $class = 'Lacebox\\Sole\\Validation\\Rules\\' . ucfirst($ruleName) . 'Rule';
                    if (class_exists($class)) {
                        $r = ($paramStr !== null && $paramStr !== '') ? new $class($paramStr) : new $class();
                        break;
                    }

                    throw new \RuntimeException("Unknown rule: {$ruleName}");
            }

            if (method_exists($r, 'setField')) $r->setField($fieldPattern);
            return $r;
        }

        throw new \RuntimeException("Unknown rule: {$entry}");
    }

    public function validate(): bool
    {
        $this->errors = [];
        $data = ShoeRequest::grab()->all();

        foreach ($this->spec as $fieldPattern => $rules) {

            $hasWildcard = (strpos($fieldPattern, '*') !== false);

            // Wildcard pattern e.g answers.*.id
            if ($hasWildcard) {
                $matches = $this->expandWildcardMatches($data, $fieldPattern);

                if (empty($matches)) {
                    // Let parent handle it, except if explicitly required
                    if ($this->hasRule($rules, RequiredRule::class)) {
                        $this->applyRules($fieldPattern, null, $data, $rules);
                    }
                    continue;
                }

                foreach ($matches as $m) {
                    $path   = $m['path'];
                    $exists = $m['exists'];
                    $value  = $m['value'];

                    if (!$exists && !$this->hasRule($rules, RequiredRule::class)) {
                        continue;
                    }

                    $this->applyRules($path, $value, $data, $rules);
                }

                continue;
            }

            // Non-wildcard basic/dotted
            $exists = $this->hasByDot($data, $fieldPattern);
            $value  = $this->getByDot($data, $fieldPattern, null);

            // "filled" cares about presence: if missing entirely, skip filled
            // required handles missing.
            if (!$exists && !$this->hasRule($rules, RequiredRule::class) && !$this->hasRule($rules, FilledRule::class)) {
                continue;
            }

            $this->applyRules($fieldPattern, $value, $data, $rules, $exists);
        }

        if (empty($this->errors)) return true;

        if ($this->throwOnFail) {
            throw new ValidationException($this->errors);
        }

        return false;
    }

    private function applyRules(string $path, $value, array $all, array $rules, bool $exists = true): void
    {
        $hasNullable = $this->hasRule($rules, NullableRule::class);

        // If nullable and value is null/empty string => skip all rules except required/filled
        if ($hasNullable && ($value === null || $value === '')) {
            foreach ($rules as $ruleObj) {
                $cls = get_class($ruleObj);
                if ($cls === RequiredRule::class) {
                    if (!$ruleObj->validate($value, $all)) {
                        $this->errors[$path][] = $ruleObj->message();
                        if ($this->firstErrorMode) return;
                    }
                } elseif ($cls === FilledRule::class) {
                    // only applies if the key exists
                    if ($exists && !$ruleObj->validate($value, $all)) {
                        $this->errors[$path][] = $ruleObj->message();
                        if ($this->firstErrorMode) return;
                    }
                }
            }
            return;
        }

        foreach ($rules as $ruleObj) {

            // filled: only if field exists
            if ($ruleObj instanceof FilledRule) {
                if (!$exists) continue;
            }

            if (!$ruleObj->validate($value, $all)) {
                $this->errors[$path][] = $ruleObj->message();
                if ($this->firstErrorMode) break;
            }
        }
    }

    private function hasRule(array $rules, string $class): bool
    {
        foreach ($rules as $r) if ($r instanceof $class) return true;
        return false;
    }

    public function errors(): array { return $this->errors; }
    public function fails(): bool { return !empty($this->errors); }
    public function first(string $field): ?string { return $this->errors[$field][0] ?? null; }

    // -------- wildcard + dot helpers (same as your version) --------

    private function expandWildcardMatches(array $data, string $pattern): array
    {
        $segments = array_values(array_filter(explode('.', trim($pattern)), 'strlen'));
        return $this->walkWildcard($data, $segments, '');
    }

    private function walkWildcard($current, array $segments, string $prefix): array
    {
        if (empty($segments)) {
            return [[
                'path'   => ltrim($prefix, '.'),
                'exists' => true,
                'value'  => $current,
            ]];
        }

        $seg = array_shift($segments);

        if ($seg === '*') {
            $out = [];
            if (is_array($current)) {
                foreach ($current as $k => $v) {
                    $newPrefix = $prefix === '' ? (string)$k : ($prefix . '.' . $k);
                    $out = array_merge($out, $this->walkWildcard($v, $segments, $newPrefix));
                }
            } else {
                $leafPath = $prefix . '.' . implode('.', $segments);
                $out[] = ['path' => ltrim($leafPath, '.'), 'exists' => false, 'value' => null];
            }
            return $out;
        }

        $newPrefix = $prefix === '' ? $seg : ($prefix . '.' . $seg);

        if (is_array($current) && array_key_exists($seg, $current)) {
            return $this->walkWildcard($current[$seg], $segments, $newPrefix);
        }

        $leafPath = $newPrefix . (empty($segments) ? '' : ('.' . implode('.', $segments)));
        return [[ 'path' => ltrim($leafPath, '.'), 'exists' => false, 'value' => null ]];
    }

    private function getByDot(array $data, string $path, $default = null)
    {
        $segs = array_values(array_filter(explode('.', trim($path)), 'strlen'));
        $cur = $data;
        foreach ($segs as $s) {
            if (!is_array($cur) || !array_key_exists($s, $cur)) return $default;
            $cur = $cur[$s];
        }
        return $cur;
    }

    private function hasByDot(array $data, string $path): bool
    {
        $segs = array_values(array_filter(explode('.', trim($path)), 'strlen'));
        $cur = $data;
        foreach ($segs as $s) {
            if (!is_array($cur) || !array_key_exists($s, $cur)) return false;
            $cur = $cur[$s];
        }
        return true;
    }
}