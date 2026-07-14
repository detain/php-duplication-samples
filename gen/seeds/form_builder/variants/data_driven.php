<?php

declare(strict_types=1);

namespace Acme\Seed\FormBuilder;

final class FormBuilderSeed
{
    // <<<PAYLOAD:form_builder>>>
    public function build(array $schema): string
    {
        $id = $schema['id'] ?? 'form';
        $method = strtoupper($schema['method'] ?? 'POST');
        $action = (string) ($schema['action'] ?? '');
        $fields = $schema['fields'] ?? [];

        $html = sprintf("<form id=\"%s\" method=\"%s\" action=\"%s\">\n", $id, $method, $action);

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

        $html .= match ($type) {
            'textarea' => sprintf("<textarea name=\"%s\" id=\"%s\"%s>%s</textarea>\n", $name, $name, $required, $value),
            'select' => $this->renderSelect($name, $field),
            'checkbox' => sprintf("<input type=\"checkbox\" name=\"%s\" id=\"%s\" value=\"1\"%s%s>\n", $name, $name, $value ? ' checked' : '', $required),
            'radio' => $this->renderRadio($name, $field),
            default => sprintf("<input type=\"%s\" name=\"%s\" id=\"%s\" value=\"%s\"%s>\n", $type, $name, $name, $value, $required),
        };

        if (!empty($field['error'])) {
            $html .= "<span class=\"error\">" . htmlspecialchars($field['error']) . "</span>\n";
        }

        $html .= "</div>\n";
        return $html;
    }

    protected function renderSelect(string $name, array $field): string
    {
        $required = ($field['required'] ?? false) ? ' required' : '';
        $options = $field['options'] ?? [];
        $selected = $field['value'] ?? '';
        $html = sprintf("<select name=\"%s\" id=\"%s\"%s>\n", $name, $name, $required);
        foreach ($options as $optVal => $optLabel) {
            $sel = ((string) $optVal === (string) $selected) ? ' selected' : '';
            $html .= sprintf("<option value=\"%s\"%s>%s</option>\n", htmlspecialchars((string) $optVal), $sel, htmlspecialchars((string) $optLabel));
        }
        $html .= "</select>\n";
        return $html;
    }

    protected function renderRadio(string $name, array $field): string
    {
        $options = $field['options'] ?? [];
        $selected = $field['value'] ?? '';
        $html = '';
        foreach ($options as $optVal => $optLabel) {
            $sel = ((string) $optVal === (string) $selected) ? ' checked' : '';
            $html .= sprintf("<input type=\"radio\" name=\"%s\" id=\"%s\" value=\"%s\"%s>\n", $name, $name, htmlspecialchars((string) $optVal), $sel);
            $html .= sprintf("<label for=\"%s\">%s</label>\n", $name, htmlspecialchars((string) $optLabel));
        }
        return $html;
    }

    public function validate(array $schema, array $data): array
    {
        $errors = [];
        foreach ($schema['fields'] ?? [] as $field) {
            $name = $field['name'] ?? '';
            $value = $data[$name] ?? null;
            $type = $field['type'] ?? 'text';

            if (($field['required'] ?? false) && ($value === null || $value === '')) {
                $errors[$name] = ucfirst($name) . ' is required';
                continue;
            }

            if ($value !== null && $value !== '') {
                if ($type === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$name] = 'Invalid email address';
                }
                if ($type === 'number' && !is_numeric($value)) {
                    $errors[$name] = 'Must be a number';
                }
                if ($type === 'number' && is_numeric($value)) {
                    if (isset($field['min']) && (float) $value < (float) $field['min']) {
                        $errors[$name] = 'Value is too small';
                    }
                    if (isset($field['max']) && (float) $value > (float) $field['max']) {
                        $errors[$name] = 'Value is too large';
                    }
                }
            }
        }
        return $errors;
    }
    // <<<END-PAYLOAD>>>
}
