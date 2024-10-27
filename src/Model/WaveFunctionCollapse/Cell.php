<?php

declare(strict_types=1);

namespace App\Model\WaveFunctionCollapse;

use function is_array;

class Cell
{
    public bool $collapsed = false;

    /**
     * @var array<int>
     */
    public array $options;

    /**
     * @param array<int>|int $value
     */
    public function __construct(array|int $value = [])
    {
        if (is_array($value)) {
            $this->options = $value;
        } else {
            $this->options = range(0, $value - 1);
        }
    }

    public function isCollapsed(): bool
    {
        return $this->collapsed;
    }

    public function setCollapsed(bool $collapsed): void
    {
        $this->collapsed = $collapsed;
    }

    /**
     * @return array<int>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array<int> $options
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }
}
