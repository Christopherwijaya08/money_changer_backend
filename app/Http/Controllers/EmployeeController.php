<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::with('branch')->where('is_active', true)->orderBy('name')->get();

        return EmployeeResource::collection($employees);
    }

    public function store(StoreEmployeeRequest $request)
    {
        $data = $request->validated();
        $data['is_active'] = $data['is_active'] ?? true;

        $employee = Employee::create($data);

        return new EmployeeResource($employee->load('branch'));
    }

    public function update(StoreEmployeeRequest $request, Employee $employee)
    {
        $data = $request->validated();
        if (! $request->has('is_active')) {
            unset($data['is_active']);
        }

        $employee->update($data);

        return new EmployeeResource($employee->load('branch'));
    }
}
