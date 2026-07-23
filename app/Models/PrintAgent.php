<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrintAgent extends Model
{
    protected $fillable = ['tenant_id', 'branch_id', 'token_hash', 'last_seen_at'];

    protected $casts = ['last_seen_at' => 'datetime'];
}
