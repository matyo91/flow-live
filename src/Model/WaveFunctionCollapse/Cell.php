<?php

namespace App\Model\WaveFunctionCollapse;

class Cell
{
    public bool $collapsed = false;
    public array $options;

    public function __construct($value)
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

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }
}
