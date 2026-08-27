<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Guardian extends Model
{
    protected $fillable = [
        'full_name', 'date_of_birth', 'phone', 'email', 'address', 'occupation',
        'religion', 'workplace', 'notes',
    ];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date'];
    }

    public function students(): BelongsToMany { return $this->belongsToMany(Student::class)->withPivot(['relationship', 'is_primary']); }
}
