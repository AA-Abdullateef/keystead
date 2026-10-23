<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    use HasUuid;

    protected $fillable = ['name'];

    public function states(): HasMany
    {
        return $this->hasMany(State::class);
    }
}
