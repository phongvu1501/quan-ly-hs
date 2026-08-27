<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    protected $fillable = ['author_id', 'title', 'content', 'type', 'audience', 'class_id', 'published_at', 'expires_at', 'is_published'];
    protected function casts(): array { return ['published_at' => 'datetime', 'expires_at' => 'datetime', 'is_published' => 'boolean']; }
    public function class(): BelongsTo { return $this->belongsTo(SchoolClass::class, 'class_id'); }
    public function author(): BelongsTo { return $this->belongsTo(User::class, 'author_id'); }
}
