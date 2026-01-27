<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportTemplate extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'keywords',
        'base_sql',
        'parameters_schema',
        'default_chart_type',
        'chart_config',
        'usage_count',
        'is_active',
    ];

    protected $casts = [
        'keywords' => 'array',
        'parameters_schema' => 'array',
        'chart_config' => 'array',
        'is_active' => 'boolean',
    ];

    public function reports(): HasMany
    {
        return $this->hasMany(AiReport::class, 'template_id');
    }

    public function incrementUsage()
    {
        $this->increment('usage_count');
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (auth()->check()) {
                $model->tenant_id = auth()->user()->tenant_id ?? 'default';
            }
        });
    }
}
