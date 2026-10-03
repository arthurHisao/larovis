<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'slug'])]
class Department extends Model
{
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }


    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}