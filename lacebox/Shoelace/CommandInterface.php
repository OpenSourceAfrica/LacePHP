<?php

namespace Lacebox\Shoelace;

interface CommandInterface
{
    /** e.g. “route:list” or “stitch controller” */
    public function name(): string;

    /** Short description for “--help” */
    public function description(): string;

    /**
     * Return true if this command wants to handle the given argv.
     */
    public function matches(array $argv): bool;

    /**
     * Execute the command. Should echo/exit as needed.
     */
    public function run(array $argv): void;
}