<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Contrib-Cycle',
    contact: new OA\Contact(email: 'jadiael1@gmail.com'),
    license: new OA\License(name: 'MIT'),
)]
#[OA\Tag(name: 'Auth', description: 'Authentication and token endpoints.')]
#[OA\Tag(name: 'Public Projects', description: 'Publicly accessible project data.')]
#[OA\Tag(name: 'Participant Projects', description: 'Participant project access and membership.')]
#[OA\Tag(name: 'Participant Payments', description: 'Participant payment operations.')]
#[OA\Tag(name: 'Admin Projects', description: 'Admin project management.')]
#[OA\Tag(name: 'Admin Members', description: 'Admin membership management.')]
#[OA\Tag(name: 'Admin Payment Methods', description: 'Admin payment method management.')]
#[OA\Tag(name: 'Admin Reports', description: 'Admin reporting endpoints.')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum',
    description: 'Use the Bearer token returned by the login endpoints.',
)]
abstract class Controller
{
    //
}
