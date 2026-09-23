<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SettingResource;
use App\Models\Setting;
use App\Traits\ApiResponser;
use App\Traits\Helper;

class SettingController extends Controller
{
    use ApiResponser, Helper;
    public function setting(): \Illuminate\Http\JsonResponse
    {
        $setting=Setting::query()->first();
        return $this->successResponse( new SettingResource($setting),200);
    }
}
