<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grade extends Model
{
    protected $fillable = ['level', 'name', 'description'];

    public function classes(): HasMany { return $this->hasMany(SchoolClass::class); }
}
