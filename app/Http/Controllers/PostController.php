<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Support\Facades\Gate;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::published()
            ->with('user')
            ->latest('published_at')
            ->paginate(20);

        return PostResource::collection($posts);
    }

    public function create()
    {
        return 'posts.create';
    }

    public function store(StorePostRequest $request)
    {
        $post = $request->user()->posts()->create($request->validated());

        return (new PostResource($post->load('user')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Post $post)
    {
        abort_if(! $post->isPublished(), 404);

        return new PostResource($post->load('user'));
    }

    public function edit(Post $post)
    {
        Gate::authorize('update', $post);

        return 'posts.edit';
    }

    public function update(UpdatePostRequest $request, Post $post)
    {
        $post->update($request->validated());

        return new PostResource($post->load('user'));
    }

    public function destroy(Post $post)
    {
        Gate::authorize('delete', $post);

        $post->delete();

        return response()->noContent();
    }
}
