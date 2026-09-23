<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostPaginationResource;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Traits\ApiResponser;
use App\Traits\TablesQuery;
use Illuminate\Http\Request;

class PostsController extends Controller
{
    use ApiResponser,TablesQuery;
    public function index()//: \Illuminate\Http\JsonResponse
    {
        return $this->successResponse(PostPaginationResource::make($this->showAllPagination(Post::query()->with(['category','user'])->get())),200);

    }
    public function allposts()
    {
        return $this->successResponse(PostPaginationResource::make($this->showAllPagination(Post::query()->with(['category','user'])->get())),200);

    }

    public function sendPosts()
    {
        $data = $this->showAllNew(Post::query()->with(['category','user'])->get());
        return $this->successResponse(PostResource::collection($data),200);
    }
    public function show(Post $post): \Illuminate\Http\JsonResponse
    {
        return $this->successResponse(new PostResource($post),200);
    }

    public function updatePostData(Request $request){
        $id = $request->id;
        $post = Post::find($id);
        return response() -> json([
            'status' => true,
            'nameEn' => $post["translations"][0]["name"],
            'nameAr' => $post["translations"][1]["name"],
            'descriptionEn' => $post["translations"][0]["description"],
            'descriptionAr' => $post["translations"][1]["description"],
        ]);
    }
    public function searchPosts(Request $request)
    {
        return $this->successResponse(PostResource::collection($this->search(Post::query(), Post::SEARCHFIELDS, $request->name)->get())->collection, 200);
    }

    public function featuredPosts(){
        return $this->successResponse(PostPaginationResource::make($this->showAllPagination(Post::query()->where('featured',1)->orderByDesc('id')->with(['category','user'])->get())),200);

    }
}
