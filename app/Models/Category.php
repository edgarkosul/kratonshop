<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Kalnoy\Nestedset\NodeTrait;

class Category extends Model
{
    use NodeTrait;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'img',
        'is_active',
        'sort',
        'meta_json',
    ];

    protected $casts = [
        'is_active' => 'bool',
        'meta_json' => 'array',
    ];

    // Удобный аксессор: полный путь из слагов
    public function getSlugPathAttribute(): string
    {
        return $this->ancestorsAndSelf()
            ->defaultOrder()
            ->pluck('slug')
            ->implode('/');
    }

    // Корневые узлы (ordered по NestedSet)
    public function scopeRoots($q)
    {
        return $q->whereIsRoot();
    }
}
