<?php

namespace App\Repositories;

use App\Models\MasterMenu;

class MasterMenuRepository
{
    public function findById(int|string $id): ?MasterMenu
    {
        return MasterMenu::query()->find($id);
    }

    public function findByCode(string $menuCode): ?MasterMenu
    {
        return MasterMenu::query()
            ->where('menu_code', $menuCode)
            ->first();
    }
}
