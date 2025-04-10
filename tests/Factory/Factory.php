<?php

namespace Tests\Factory;

interface Factory
{
    public function make(array $attributes = []): array;
    public function create(array $attributes = []): array;
}

abstract class AbstractFactory implements Factory
{
    protected array $defaultAttributes = [];

    public function make(array $attributes = []): array
    {
        return array_merge($this->defaultAttributes, $attributes);
    }

    public function create(array $attributes = []): array
    {
        $data = $this->make($attributes);
        return $this->persist($data);
    }

    abstract protected function persist(array $data): array;
}
