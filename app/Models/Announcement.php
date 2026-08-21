<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = ['author_id', 'title', 'content', 'type', 'audience', 'class_id', 'published_at', 'expires_at', 'is_published'];
    protected function casts(): array { return ['published_at' => 'datetime', 'expires_at' => 'datetime', 'is_published' => 'boolean']; }
}
