<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectiveProjectPayment extends Model
{
    protected $fillable = [
        'collective_project_id',
        'user_id',
        'period_year',
        'period_month',
        'period_week_of_month',
        'sequence_in_period',
        'amount',
        'paid_at',
        'receipt_path',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(CollectiveProject::class, 'collective_project_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
