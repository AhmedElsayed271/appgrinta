<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CountryResource;
use App\Models\Country;
use App\Traits\ApiResponser;

class CountriesController extends Controller
{
    use ApiResponser;
    public function index(): \Illuminate\Http\JsonResponse
    {
        return $this->showAll(CountryResource::collection(Country::all())->collection);
    }
    public function show(Country $country): \Illuminate\Http\JsonResponse
    {
        return $this->successResponse(new CountryResource($country),200);
    }
}
