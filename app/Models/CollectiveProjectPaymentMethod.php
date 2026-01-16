<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectiveProjectPaymentMethod extends Model
{
    protected $table = 'collective_project_payment_methods';

    protected $fillable = [
        'collective_project_id',
        'payment_method_type',
        'payment_method_payload',
        'label',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        // criptografa em repouso e entrega como array no PHP
        'payment_method_payload' => 'encrypted:array',
        'is_active' => 'boolean',
    ];

    protected $hidden = ['payment_method_payload'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(CollectiveProject::class, 'collective_project_id');
    }
}
