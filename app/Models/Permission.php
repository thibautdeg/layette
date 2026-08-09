<?php

namespace App\Models;

use App\Enums\Permission as PermissionEnum;
use Laratrust\Models\Permission as BasePermission;

class Permission extends BasePermission
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
    ];

    public static function findByName(PermissionEnum $name): self
    {
        return self::query()->where('name', $name->value)->firstOrFail();
    }
}
