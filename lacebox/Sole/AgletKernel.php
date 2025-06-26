<?php
namespace Lacebox\Sole;

class AgletKernel
{
    /** @var array[] */
    protected $codeTasks = [];

    /**
     * Register a task in code.
     *
     * @param string $name
     * @param string $cron        e.g. '0 * * * *'
     * @param string $handler     'Class@method' or shell
     */
    public function task(string $name, string $cron, string $handler): void
    {
        $this->codeTasks[] = compact('name','cron','handler');
    }

    /**
     * @return array[]  All code-registered tasks
     */
    public function getCodeTasks(): array
    {
        return $this->codeTasks;
    }
}