<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Guardian extends Model
{
    protected $fillable = ['full_name', 'phone', 'email', 'address', 'occupation'];

    public function students(): BelongsToMany { return $this->belongsToMany(Student::class)->withPivot(['relationship', 'is_primary']); }
}
