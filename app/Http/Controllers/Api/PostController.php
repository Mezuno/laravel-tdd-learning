<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Post\StoreRequest;
use App\Http\Requests\Api\Post\UpdateRequest;
use App\Http\Resources\Post\PostResuorce;
use Illuminate\Support\Facades\Storage;
use App\Models\Post;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::all();
        return PostResuorce::collection($posts)->resolve();
    }

    public function show(Post $post)
    {
        return PostResuorce::make($post)->resolve();
    }

    public function store(StoreRequest $request)
    {
        $data = $request->validated();

        if (!empty($data['image'])) {
            $path = Storage::disk('fake_storage')->put('/images', $data['image']);
            $data['image_url'] = $path;
        }

        unset($data['image']);

        $post = Post::create($data);

        return PostResuorce::make($post)->resolve();
    }

    public function update(UpdateRequest $request, Post $post)
    {
        $data = $request->validated();

        if (!empty($data['image'])) {
            $path = Storage::disk('fake_storage')->put('/images', $data['image']);
            $data['image_url'] = $path;
        }

        unset($data['image']);

        $post->update($data);

//        Если не обновляется объект при ->update()
//        $post = $post->fresh();

        return PostResuorce::make($post)->resolve();
    }

    public function destroy(Post $post)
    {
        $post->delete();

        return response()->json([
            'message' => 'deleted',
        ]);
    }
}
