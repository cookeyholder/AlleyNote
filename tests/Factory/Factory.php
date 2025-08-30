<?php

namespace Tests\Factory;

use Tests\Factory\Abstracts\Factory;

abstract class AbstractFactory implements Factory
{
    protected array<mixed> $defaultAttributes = [];

    public function make(array<mixed> $attributes = []): array<mixed>
    {
        return array_merge($this->defaultAttributes, $attributes);
    }

    public function create(array<mixed> $attributes = []): array<mixed>
    {
        $data = $this->make($attributes);

        return $this->persist($data);
    }

    abstract protected function persist(array<mixed> $data): array<mixed>;
}
