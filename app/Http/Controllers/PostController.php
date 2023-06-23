<?php

namespace App\Http\Controllers;

use App\Http\Requests\Post\StoreRequest;
use App\Http\Requests\Post\UpdateRequest;
use App\Models\Post;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::all();
        return view('post.index', compact('posts'));
    }

    public function show(Post $post)
    {
        return view('post.show', compact('post'));
    }
    public function store(StoreRequest $request)
    {
        $data = $request->validated();

        if (!empty($data['image'])) {
            $path = Storage::disk('fake_storage')->put('/images', $data['image']);
            $data['image_url'] = $path;
        }

        unset($data['image']);

        Post::create($data);
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
    }
}
