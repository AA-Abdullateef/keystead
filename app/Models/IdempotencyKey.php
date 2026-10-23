<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class IdempotencyKey extends Model
{
    use HasUuid;

    protected $guarded = [];
}
