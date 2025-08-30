<?php

namespace Tests\Factory\Abstracts;

interface Factory
{
    public function make(array $attributes = []): array;

    public function create(array $attributes = []): array;
}
