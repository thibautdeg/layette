<?php

namespace App\Models;

use App\Enums\Role as RoleEnum;
use Laratrust\Models\Role as BaseRole;

class Role extends BaseRole
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
    ];

    public static function findByName(RoleEnum $name): self
    {
        return self::query()->where('name', $name->value)->firstOrFail();
    }
}
