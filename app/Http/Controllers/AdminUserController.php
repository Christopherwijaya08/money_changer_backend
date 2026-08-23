<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdminUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

// ponytail: Kelola Akses is Owner-only in the frontend (per PRD); enforced here
// too since account management (roles, password resets) is squarely sensitive.
class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->role === 'owner', 403);

        return UserResource::collection(User::orderBy('name')->get());
    }

    public function store(StoreAdminUserRequest $request)
    {
        abort_unless($request->user()->role === 'owner', 403);

        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
        $data['is_active'] = $data['is_active'] ?? true;

        $user = User::create($data);

        return new UserResource($user);
    }

    public function update(StoreAdminUserRequest $request, User $user)
    {
        abort_unless($request->user()->role === 'owner', 403);

        $data = $request->validated();
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        if (! $request->has('is_active')) {
            unset($data['is_active']);
        }

        $user->update($data);

        return new UserResource($user);
    }
}
