<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::with('user')
            ->where('is_draft', false)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->paginate(20);

        return response()->json($posts);
    }

    public function create()
    {
        return 'posts.create';
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'is_draft' => ['boolean'],
            'published_at' => ['nullable', 'date'],
        ]);

        $post = $request->user()->posts()->create($validated);

        return response()->json($post, 201);
    }

    public function show(Post $post)
    {
        if ($post->is_draft || ! $post->published_at || $post->published_at->isFuture()) {
            abort(404);
        }

        $post->load('user');

        return response()->json($post);
    }

    public function edit(Post $post)
    {
        abort_if($post->user_id !== request()->user()->id, 403);

        return 'posts.edit';
    }

    public function update(Request $request, Post $post)
    {
        abort_if($post->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'is_draft' => ['boolean'],
            'published_at' => ['nullable', 'date'],
        ]);

        $post->update($validated);

        return response()->json($post);
    }
}