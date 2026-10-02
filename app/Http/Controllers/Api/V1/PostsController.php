<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostPaginationResource;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Models\PostReaction;
use App\Traits\ApiResponser;
use App\Traits\TablesQuery;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PostsController extends Controller
{
    use ApiResponser,TablesQuery;
    public function index()//: \Illuminate\Http\JsonResponse
    {
        return $this->successResponse(PostPaginationResource::make($this->showAllPagination(Post::query()->published()->with(['category','user'])->get())),200); // [reactions-off]

    }
    public function allposts()
    {
        return $this->successResponse(PostPaginationResource::make($this->showAllPagination(Post::query()->published()->with(['category','user'])->get())),200); // [reactions-off]

    }

    public function sendPosts()
    {
        $data = $this->showAllNew(Post::query()->published()->with(['category','user'])->get()); // [reactions-off]
        return $this->successResponse(PostResource::collection($data),200);
    }
    public function show(Post $post): \Illuminate\Http\JsonResponse
    {
        if ($post->isScheduled()) {
            return $this->errorResponse('Post not found', 404);
        }
        return $this->successResponse(new PostResource($post),200); // [reactions-off] removed load('reactions')
    }

    public function react(Request $request, Post $post): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'type' => ['required', Rule::in(PostReaction::TYPES)],
        ]);

        $type     = $request->input('type');
        $clientId = $request->user()->getAuthIdentifier();

        $reaction = PostReaction::where('post_id', $post->id)
            ->where('client_id', $clientId)
            ->first();

        if ($reaction) {
            if ($reaction->type === $type) {
                $reaction->delete();
                $action      = 'removed';
                $currentType = null;
            } else {
                $reaction->update(['type' => $type]);
                $action      = 'updated';
                $currentType = $type;
            }
        } else {
            PostReaction::create([
                'post_id'   => $post->id,
                'client_id' => $clientId,
                'type'      => $type,
            ]);
            $action      = 'added';
            $currentType = $type;
        }

        $post->load('reactions');

        return $this->successResponse([
            'post_id'   => (int)$post->id,
            'action'    => $action,
            'type'      => $currentType,
            // 'reactions' => (new PostResource($post))->toArray($request)['reactions'], // [reactions-off]
        ], 200);
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
        return $this->successResponse(PostResource::collection($this->search(Post::query()->published(), Post::SEARCHFIELDS, $request->name)->get())->collection, 200);
    }

    public function featuredPosts(){
        return $this->successResponse(PostPaginationResource::make($this->showAllPagination(Post::query()->published()->where('featured',1)->orderByDesc('id')->with(['category','user'])->get())),200);

    }
}
