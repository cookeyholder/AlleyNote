<?php

declare(strict_types=1);

namespace App\Domains\Post\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * 標籤 Model.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $color
 * @property int $usage_count
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Tag extends Model
{
    /** @var string */
    protected $table = 'tags';

    /** @var array<int, string> */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'usage_count',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'usage_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 與文章的多對多關係.
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_tags', 'tag_id', 'post_id');
    }

    /**
     * 轉換為陣列.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'color' => $this->color,
            'usage_count' => $this->usage_count,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
