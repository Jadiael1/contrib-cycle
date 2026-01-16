<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMembership extends Model
{
    protected $fillable = [
        'collective_project_id',
        'user_id',
        'status',
        'accepted_at',
        'removed_at',
        'removed_by_user_id',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
        'removed_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(CollectiveProject::class, 'collective_project_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function removedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'removed_by_user_id');
    }
}
