<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiReport extends Model
{
    protected $fillable = [
        'tenant_id',
        'branch_id',
        'user_id',
        'question',
        'interpretation',
        'sql_query',
        'parameters',
        'result_data',
        'chart_type',
        'chart_config',
        'is_favorite',
        'template_id',
        'status',
        'error_message',
    ];

    protected $casts = [
        'parameters' => 'array',
        'result_data' => 'array',
        'chart_config' => 'array',
        'is_favorite' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ReportTemplate::class);
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (auth()->check()) {
                $model->tenant_id = auth()->user()->tenant_id ?? 'default';
                $model->branch_id = $model->branch_id ?? session('branch_id');
                $model->user_id = $model->user_id ?? auth()->id();
            }
        });
    }
}
