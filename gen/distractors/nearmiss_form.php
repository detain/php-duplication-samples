<?php

declare(strict_types=1);

namespace __NAMESPACE__;

final class __CLASS__
{
    // <<<NEARMISS>>>
    public function build(array $schema): string
    {
        $id = $schema['id'] ?? 'form';
        $method = $schema['method'] ?? 'POST';
        $action = $schema['action'] ?? '';
        $fields = $schema['fields'] ?? [];

        $html = "<form id=\"{$id}\" method=\"{$method}\" action=\"{$action}\">\n";

        foreach ($fields as $field) {
            $html .= $this->renderField($field);
        }

        $html .= "<button type=\"submit\">Submit</button>\n";
        $html .= "</form>\n";

        return $html;
    }

    protected function renderField(array $field): string
    {
        $type = $field['type'] ?? 'text';
        $name = $field['name'] ?? '';
        $label = $field['label'] ?? ucwords(str_replace('_', ' ', $name));
        $required = ($field['required'] ?? false) ? ' required' : '';
        $value = htmlspecialchars((string) ($field['value'] ?? ''));

        $html = "<div class=\"field\">\n";
        $html .= "<label for=\"{$name}\">{$label}</label>\n";

        if ($type === 'textarea') {
            $html .= "<textarea name=\"{$name}\" id=\"{$name}\"{$required}>{$value}</textarea>\n";
        } elseif ($type === 'select') {
            $options = $field['options'] ?? [];
            $html .= "<select name=\"{$name}\" id=\"{$name}\"{$required}>\n";
            foreach ($options as $optionValue => $optionLabel) {
                $selected = ($field['value'] ?? '') === $optionValue ? ' selected' : '';
                $html .= "<option value=\"{$optionValue}\"{$selected}>{$optionLabel}</option>\n";
            }
            $html .= "</select>\n";
        } elseif ($type === 'checkbox') {
            $checked = $value ? ' checked' : '';
            $html .= "<input type=\"checkbox\" name=\"{$name}\" id=\"{$name}\" value=\"1\"{$checked}{$required}>\n";
        } elseif ($type === 'radio') {
            $options = $field['options'] ?? [];
            foreach ($options as $optionValue => $optionLabel) {
                $checked = ($field['value'] ?? '') === $optionValue ? ' checked' : '';
                $html .= "<input type=\"radio\" name=\"{$name}\" id=\"{$name}\" value=\"{$optionValue}\"{$checked}>\n";
                $html .= "<label for=\"{$name}\">{$optionLabel}</label>\n";
            }
        } else {
            $html .= "<input type=\"{$type}\" name=\"{$name}\" id=\"{$name}\" value=\"{$value}\"{$required}>\n";
        }

        if (isset($field['error'])) {
            $html .= "<span class=\"error\">" . htmlspecialchars($field['error']) . "</span>\n";
        }

        $html .= "</div>\n";
        return $html;
    }

    public function validate(array $schema, array $data): array
    {
        $errors = [];
        foreach ($schema['fields'] ?? [] as $field) {
            $name = $field['name'] ?? '';
            $value = $data[$name] ?? null;

            if (($field['required'] ?? false) && ($value === null || $value === '')) {
                $errors[$name] = ucfirst($name) . ' is required';
                continue;
            }

            if ($value !== null && $value !== '') {
                $type = $field['type'] ?? 'text';
                if ($type === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$name] = 'Invalid email address';
                }
                if ($type === 'number') {
                    if (!is_numeric($value)) {
                        $errors[$name] = 'Must be a number';
                    } else {
                        if (isset($field['min']) && (float) $value < (float) $field['min']) {
                            $errors[$name] = 'Value is too small';
                        }
                        if (isset($field['max']) && (float) $value > (float) $field['max']) {
                            $errors[$name] = 'Value is too large';
                        }
                    }
                }
            }
        }
        return $errors;
    }
    // <<<END-NEARMISS>>>
}
