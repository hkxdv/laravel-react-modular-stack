<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * Value Object para representar una estadística enriquecida del panel.
 */
class EnhancedStat implements \JsonSerializable
{
    public function __construct(
        public readonly string $key,
        public readonly string $title,
        public readonly string $description,
        public readonly string $icon,
        public readonly int|float $value
    ) {}

    /**
     * Serializa la estadística para enviarla al frontend.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'key' => $this->key,
            'title' => $this->title,
            'description' => $this->description,
            'icon' => $this->icon,
            'value' => $this->value,
        ];
    }
}