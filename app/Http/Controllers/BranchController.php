<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBranchRequest;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $branches = Branch::query()
            ->when($request->boolean('active_only'), fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->get();

        return BranchResource::collection($branches);
    }

    public function store(StoreBranchRequest $request)
    {
        $data = $request->validated();
        $data['is_active'] = $data['is_active'] ?? true;

        $branch = Branch::create($data);

        return new BranchResource($branch);
    }

    public function show(Branch $branch)
    {
        return new BranchResource($branch);
    }

    public function update(StoreBranchRequest $request, Branch $branch)
    {
        $data = $request->validated();
        if (! $request->has('is_active')) {
            unset($data['is_active']);
        }

        $branch->update($data);

        return new BranchResource($branch);
    }

    public function destroy(Branch $branch)
    {
        $branch->delete();

        return response()->noContent();
    }
}
