<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class State extends Model
{
    use HasUuid;

    protected $fillable = ['name', 'country_id'];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
