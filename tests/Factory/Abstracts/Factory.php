<?php

namespace Tests\Factory\Abstracts;

interface Factory
{
    public function make(array<mixed> $attributes = []): array<mixed>;

    public function create(array<mixed> $attributes = []): array<mixed>;
}
