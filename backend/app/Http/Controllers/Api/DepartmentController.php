<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DepartmentResource;
use App\Services\Api\DepartmentService;

class DepartmentController extends Controller
{
    public function __construct(private DepartmentService $departmentService) {}

    public function index()
    {
        return DepartmentResource::collection($this->departmentService->getAll());
    }
}
