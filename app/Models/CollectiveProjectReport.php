<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectiveProjectReport extends Model
{
    protected $fillable = [
        'collective_project_id',
        'created_by_user_id',
        'type',
        'status',
        'filters',
        'disk',
        'path',
        'file_name',
        'mime_type',
        'file_size',
        'generated_at',
        'error_message',
    ];

    protected $casts = [
        'filters' => 'array',
        'generated_at' => 'datetime',
        'file_size' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(CollectiveProject::class, 'collective_project_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
