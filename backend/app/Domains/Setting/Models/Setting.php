<?php

declare(strict_types=1);

namespace App\Domains\Setting\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * 系統設定 Model.
 *
 * @property int $id
 * @property string $key
 * @property string|null $value
 * @property string|null $type
 * @property string|null $description
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Setting extends Model
{
    protected $table = 'settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 取得設定值（根據類型轉換）.
     */
    public function getValue(): mixed
    {
        return match ($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->value,
            'float' => (float) $this->value,
            'array', 'json' => json_decode($this->value ?? '[]', true),
            default => $this->value,
        };
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
            'key' => $this->key,
            'value' => $this->getValue(),
            'type' => $this->type,
            'description' => $this->description,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
