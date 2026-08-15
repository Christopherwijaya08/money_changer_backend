<?php

namespace App\Http\Controllers;

use App\Http\Resources\EmployeeResource;
use App\Models\Employee;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::where('is_active', true)->orderBy('name')->get();

        return EmployeeResource::collection($employees);
    }
}
