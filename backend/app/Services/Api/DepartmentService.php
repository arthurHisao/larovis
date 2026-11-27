<?php

namespace App\Services\Api;

use App\Models\Department;
use Illuminate\Database\Eloquent\Collection;

class DepartmentService
{
    public function getAll(): Collection
    {
        return Department::orderBy('name', 'asc')->get();
    }
}
