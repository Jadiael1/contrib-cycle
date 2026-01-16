<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollectiveProject extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'participant_limit',
        'amount_per_participant',
        'payment_interval',
        'payments_per_interval',
        'is_active',
        'created_by_user_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'amount_per_participant' => 'decimal:2',
    ];

    public function memberships(): HasMany
    {
        return $this->hasMany(ProjectMembership::class, 'collective_project_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(CollectiveProjectPaymentMethod::class, 'collective_project_id')
            ->orderBy('sort_order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CollectiveProjectPayment::class, 'collective_project_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(CollectiveProjectReport::class, 'collective_project_id');
    }
}
