<?php

declare(strict_types=1);

namespace Acme\Seed\TemplateRenderer;

/**
 * Seed payload: simple template rendering with {{placeholder}} substitution.
 * Supports default values via {{placeholder:default}} syntax.
 */
final class TemplateRendererSeed
{
    // <<<PAYLOAD:template_renderer>>>
    public function renderTemplate(string $template, array $data): string
    {
        $result = preg_replace_callback(
            '/\{\{(\w+)(?::([^\}]*))?\}\}/',
            function ($matches) use ($data) {
                $key = $matches[1];
                if (isset($data[$key])) {
                    return (string) $data[$key];
                }
                return count($matches) > 2 ? $matches[2] : '{{' . $key . '}}';
            },
            $template
        );
        return $result;
    }
    // <<<END-PAYLOAD>>>
}
