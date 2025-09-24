<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use SolutionForest\FilamentTree\Concern\ModelTree;

class Category extends Model
{
    use ModelTree;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'img',
        'is_active',
        'order',
        'meta_json',
    ];

    protected $casts = [
        'is_active' => 'bool',
        'meta_json' => 'array',
    ];

    public static function defaultParentKey(): int
    {
        return -1;
    } // root

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('order');
    }

    public function scopeRoots($q)
    {
        return $q->where('parent_id', -1)->orderBy('order');
    }

    public function getSlugPathAttribute(): string
    {
        $segments = [];
        $node = $this;
        while ($node && $node->parent_id !== -1) {
            $segments[] = $node->slug;
            $node = $node->parent;
        }
        if ($node && $node->parent_id === -1) {
            $segments[] = $node->slug;
        }
        return implode('/', array_reverse($segments));
    }
}
