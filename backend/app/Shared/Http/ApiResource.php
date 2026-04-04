<?php

declare(strict_types=1);

namespace App\Shared\Http;

abstract class ApiResource
{
    public function __construct(
        protected readonly mixed $resource,
        /** @var array<string, mixed> */
        protected readonly array $context = [],
    ) {}

    /** @return array<string, mixed> */
    abstract public function toArray(): array;

    /** @return array<string, mixed> */
    public function resolve(): array
    {
        return $this->toArray();
    }

    /**
     * @template TResource
     * @param iterable<TResource> $items
     * @param class-string<self> $resourceClass
     * @param array<string, mixed> $context
     * @return array<int, array<string, mixed>>
     */
    public static function collection(iterable $items, string $resourceClass, array $context = []): array
    {
        $result = [];
        foreach ($items as $item) {
            /** @var self $resource */
            $resource = new $resourceClass($item, $context);
            $result[] = $resource->resolve();
        }

        return $result;
    }
}
