<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    use HasUuid, Audited;

    protected $fillable = [
        'user_id', 'first_name', 'last_name', 'gender', 'dob', 'phone', 'occupation',
        'address', 'city', 'state_id', 'country_id', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return ['dob' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }
}
