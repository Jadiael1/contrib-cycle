<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'User',
    type: 'object',
    required: ['id', 'role'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', description: 'User ID', example: 1),
        new OA\Property(property: 'role', type: 'string', enum: ['admin', 'participant'], example: 'admin'),
        new OA\Property(property: 'username', type: 'string', nullable: true, example: 'admin'),
        new OA\Property(property: 'phone', type: 'string', nullable: true, example: '+5581999999999'),
        new OA\Property(property: 'first_name', type: 'string', nullable: true, example: 'John'),
        new OA\Property(property: 'last_name', type: 'string', nullable: true, example: 'Doe'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'role',
        'username',
        'phone',
        'first_name',
        'last_name',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function collectiveProjectPayments(): HasMany
    {
        return $this->hasMany(CollectiveProjectPayment::class, 'user_id');
    }
}
