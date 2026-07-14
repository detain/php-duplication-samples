<?php

declare(strict_types=1);

namespace __NAMESPACE__;

final class __CLASS__
{
    public function createInput(string $name, string $type = 'text'): string
    {
        return "<input type=\"{$type}\" name=\"{$name}\">";
    }

    public function createSelect(string $name, array $options): string
    {
        $html = "<select name=\"{$name}\">";
        foreach ($options as $value => $label) {
            $html .= "<option value=\"{$value}\">{$label}</option>";
        }
        $html .= "</select>";
        return $html;
    }
}
