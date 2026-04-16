<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SiteCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'parent_id',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ── Boot ────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (SiteCategory $category) {
            if (empty($category->slug)) {
                $category->slug = static::generateUniqueSlug($category->name);
            }
        });

        static::updating(function (SiteCategory $category) {
            if ($category->isDirty('name') && ! $category->isDirty('slug')) {
                $category->slug = static::generateUniqueSlug($category->name, $category->id);
            }
        });
    }

    /**
     * Generate a unique slug, suffixing with -2, -3, ... on collision.
     */
    protected static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'categorie';
        $slug = $base;
        $i    = 2;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn (Builder $q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    // ── Relationships ───────────────────────────────────────────────

    public function parent(): BelongsTo
    {
        return $this->belongsTo(SiteCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(SiteCategory::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class, 'site_category_id');
    }

    // ── Scopes ──────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // ── Helpers ─────────────────────────────────────────────────────

    /**
     * Return the IDs of this category and all its descendants (recursive).
     * Useful for "filter by category including sub-categories".
     */
    public function descendantIds(): array
    {
        $ids = [$this->id];

        foreach ($this->children as $child) {
            $ids = array_merge($ids, $child->descendantIds());
        }

        return $ids;
    }

    /**
     * Build a breadcrumb string of the full path: "Rapallo > Parc Casale > Zone Nord".
     */
    public function breadcrumb(string $separator = ' > '): string
    {
        $parts = [$this->name];
        $node  = $this->parent;

        while ($node) {
            array_unshift($parts, $node->name);
            $node = $node->parent;
        }

        return implode($separator, $parts);
    }

    /**
     * Depth in the tree (0 = root).
     */
    public function depth(): int
    {
        $depth = 0;
        $node  = $this->parent;

        while ($node) {
            $depth++;
            $node = $node->parent;
        }

        return $depth;
    }
}
