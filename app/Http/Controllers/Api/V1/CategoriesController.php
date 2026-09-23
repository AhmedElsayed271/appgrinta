<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Traits\ApiResponser;

class CategoriesController extends Controller
{
    use ApiResponser;
    public function index(): \Illuminate\Http\JsonResponse//: \Illuminate\Http\JsonResponse
    {
//        return $this->getQuery(
//            'categories',
//            [
//            ],
//            [
//                ['left','category_translations','category_translations.category_id','=','categories.id']
//            ]
//            ,null
//        )->where('locale',app()->getLocale())->get();
        return $this->showAll(CategoryResource::collection(Category::query()->where('id','!=',15)->get()->load('parent'))->collection);
        //return $this->showAll(CategoryResource::collection(Category::whereNotIn('id', 15)->get()->load('parent'))->collection);
    }

    public function show(Category $category): \Illuminate\Http\JsonResponse
    {
        return $this->successResponse(new CategoryResource($category),200);
    }
}
