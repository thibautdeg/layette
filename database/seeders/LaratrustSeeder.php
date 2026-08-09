<?php

namespace Database\Seeders;

use App\Enums\Permission as PermissionEnum;
use App\Enums\Role as RoleEnum;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class LaratrustSeeder extends Seeder
{
    public function run(): void
    {
        Permission::upsert(
            collect(PermissionEnum::cases())->map(fn (PermissionEnum $permission): array => ['name' => $permission->value])->toArray(),
            ['name']
        );

        Role::upsert(
            collect(RoleEnum::cases())->map(fn (RoleEnum $role): array => ['name' => $role->value])->toArray(),
            ['name']
        );

        Role::findByName(RoleEnum::Admin)->syncPermissions(
            Permission::query()->whereIn('name', [
                PermissionEnum::ManageGifts->value,
                PermissionEnum::ManageContributions->value,
                PermissionEnum::ManageBankTransactions->value,
                PermissionEnum::ManageSettings->value,
            ])->pluck('id')->toArray()
        );

        User::doesntHave('roles')->each(function (User $user): void {
            $user->syncRoles([RoleEnum::Donor->value]);
        }, 50);
    }
}
