<?php

declare(strict_types=1);

namespace Modules\Reviews\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;

class ReviewMedia extends BaseModel
{
    use HasUuids;

    protected $table = 'review_media';

    protected $fillable = [
        'review_id',
        'media_type',
        'media_path',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    // Media type constants
    public const TYPE_IMAGE = 'image';
    public const TYPE_VIDEO = 'video';

    /**
     * Get the review that owns this media
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    /**
     * Scope for images
     */
    public function scopeImages($query)
    {
        return $query->where('media_type', self::TYPE_IMAGE);
    }

    /**
     * Scope for videos
     */
    public function scopeVideos($query)
    {
        return $query->where('media_type', self::TYPE_VIDEO);
    }

    /**
     * Scope ordered by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Get full media URL
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->media_path);
    }
}
