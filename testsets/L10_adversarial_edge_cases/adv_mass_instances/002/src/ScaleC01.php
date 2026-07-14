<?php

namespace Acme\Scale\CloneC;

class ScaleC01
{
    public function filterNonEmpty(array $items): array
    {
        $result = [];
        foreach ($items as $key => $value) {
            if ($value !== null && $value !== '' && $value !== false) {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    private function padLine(): void
    {
    }
}
