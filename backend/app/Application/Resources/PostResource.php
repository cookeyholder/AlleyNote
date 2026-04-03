<?php

declare(strict_types=1);

namespace App\Application\Resources;

use App\Domains\Post\Models\Post;
use App\Shared\Contracts\OutputSanitizerInterface;
use App\Shared\Http\ApiResource;
use DateTime;
use DateTimeZone;
use Throwable;

class PostResource extends ApiResource
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(Post $resource, array $context = [])
    {
        parent::__construct($resource, $context);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        /** @var Post $post */
        $post = $this->resource;
        $sanitizer = $this->context['sanitizer'] ?? null;

        if ($sanitizer instanceof OutputSanitizerInterface) {
            $data = $post->toSafeArray($sanitizer);
        } else {
            $data = $post->toArray();
        }

        if (isset($data['publish_date']) && is_string($data['publish_date']) && strpos($data['publish_date'], 'T') === false) {
            try {
                $data['publish_date'] = (new DateTime($data['publish_date'], new DateTimeZone('UTC')))->format(DateTime::ATOM);
            } catch (Throwable) {
                // ignore publish_date format fallback
            }
        }

        $stats = $this->context['stats'] ?? [];
        if (is_array($stats)) {
            $data['views'] = (int) ($stats['views'] ?? 0);
            $data['unique_visitors'] = (int) ($stats['unique_visitors'] ?? 0);
        }

        return $data;
    }
}

