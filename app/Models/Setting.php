<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Traits\BelongsToBranch;

class Setting extends Model
{
    use HasFactory, BelongsToBranch;

    protected $fillable = ['tenant_id', 'branch_id', 'key', 'value'];
}
