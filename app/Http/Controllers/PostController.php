<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PostController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $posts = Post::with('user')
            ->active()
            ->orderByDesc('published_at')
            ->paginate(20);

        return PostResource::collection($posts);
    }

    public function create(): string
    {
        return 'posts.create';
    }

    public function store(StorePostRequest $request): PostResource
    {
        $this->authorize('create', Post::class);

        $post = $request->user()->posts()->create($request->validated());

        return new PostResource($post);
    }

    public function show(Post $post): PostResource
    {
        $this->authorize('view', $post);

        $post->load('user');

        return new PostResource($post);
    }

    public function edit(Post $post): string
    {
        $this->authorize('update', $post);

        return 'posts.edit';
    }

    public function update(UpdatePostRequest $request, Post $post): PostResource
    {
        $post->update($request->validated());

        return new PostResource($post);
    }

    public function destroy(Post $post): Response
    {
        $this->authorize('delete', $post);

        $post->delete();

        return response()->noContent();
    }
}
