<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
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

    protected $appends = ['image_url'];


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

    public function getImageUrlAttribute(): ?string
    {
        $path = $this->img;
        if (! $path) return null;

        // абсолютные URL оставляем
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return $disk->url($path);
    }

    protected static function booted(): void
    {
        // удаляем старый файл при замене
        static::updating(function (Category $m) {
            if ($m->isDirty('img')) {
                $old = $m->getOriginal('img');
                if ($old && !str_starts_with($old, 'http')) {
                    Storage::disk('public')->delete($old);
                }
            }
        });

        // удаляем файл при удалении записи
        static::deleting(function (Category $m) {
            if ($m->img && !str_starts_with($m->img, 'http')) {
                Storage::disk('public')->delete($m->img);
            }
        });
    }
}
