<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PlantPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'plant_id',
        'image',
        'title',
        'description',
        'photo_type',
        'photographer_id',
        'taken_date',
        'camera_model',
        'focal_length',
        'aperture',
        'iso',
        'width',
        'height',
        'file_size',
        'is_main_photo',
        'display_order',
        'is_public',
    ];

    protected $appends = ['image_url'];

    protected function casts(): array
    {
        return [
            'taken_date'    => 'date',
            'is_main_photo' => 'boolean',
            'is_public'     => 'boolean',
        ];
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? url("/api/v1/plant-photos/{$this->id}/image") : null;
    }

    /**
     * Plant photo type options.
     */
    public const PHOTO_TYPES = [
        'general' => 'Vue generale',
        'leaves'  => 'Feuillage',
        'flowers' => 'Fleurs',
        'fruits'  => 'Fruits',
        'bark'    => 'Ecorce',
        'habitat' => 'Habitat',
        'detail'  => 'Detail',
    ];

    // ── Boot ────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::saving(function (PlantPhoto $photo) {
            // Extract image dimensions and file size for new uploads
            if ($photo->isDirty('image') && $photo->image) {
                try {
                    foreach (['local', 'public'] as $disk) {
                        if (! Storage::disk($disk)->exists($photo->image)) {
                            continue;
                        }

                        $path = Storage::disk($disk)->path($photo->image);
                        if (file_exists($path)) {
                            $imageInfo = getimagesize($path);
                            if ($imageInfo) {
                                $photo->width = $imageInfo[0];
                                $photo->height = $imageInfo[1];
                            }
                            $photo->file_size = filesize($path);
                            break;
                        }
                    }
                } catch (\Throwable) {
                    // Metadata extraction is optional; do not fail the save
                }
            }

            // Ensure only one main photo per plant
            if ($photo->is_main_photo) {
                static::where('plant_id', $photo->plant_id)
                    ->where('is_main_photo', true)
                    ->when($photo->exists, fn ($q) => $q->where('id', '!=', $photo->id))
                    ->update(['is_main_photo' => false]);
            }
        });
    }

    // ── Relationships ───────────────────────────────────────────────

    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }

    public function photographer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'photographer_id');
    }
}
