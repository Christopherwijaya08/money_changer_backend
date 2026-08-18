<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingRequest;
use App\Http\Resources\SettingResource;
use App\Models\Setting;

class SettingController extends Controller
{
    public function show()
    {
        return new SettingResource(Setting::current()->load('updatedBy'));
    }

    public function update(UpdateSettingRequest $request)
    {
        $data = $request->validated();

        $setting = Setting::current();
        $setting->update([
            'review_threshold' => $data['review_threshold'],
            'updated_by' => $data['user_id'],
        ]);

        return new SettingResource($setting->load('updatedBy'));
    }
}
