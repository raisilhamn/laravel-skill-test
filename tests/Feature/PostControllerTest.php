<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostControllerTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // 4-1: posts.index
    // =========================================================================

    public function test_index_returns_paginated_published_posts(): void
    {
        $user = User::factory()->create();

        // Create 25 published posts
        Post::factory()->count(25)->create([
            'user_id' => $user->id,
            'is_draft' => false,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->getJson('/posts');

        $response->assertOk();
        $response->assertJsonCount(20, 'data');
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'title', 'content', 'is_draft', 'published_at', 'author'],
            ],
            'links',
            'meta',
        ]);
    }

    public function test_index_excludes_draft_posts(): void
    {
        $user = User::factory()->create();

        Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => true,
            'published_at' => null,
        ]);

        Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => false,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->getJson('/posts');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_index_excludes_scheduled_posts(): void
    {
        $user = User::factory()->create();

        // Scheduled post (future published_at)
        Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => false,
            'published_at' => now()->addDays(3),
        ]);

        // Published post
        Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => false,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->getJson('/posts');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_index_includes_author_data(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);

        Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => false,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->getJson('/posts');

        $response->assertOk();
        $response->assertJsonPath('data.0.author.name', 'John Doe');
        $response->assertJsonPath('data.0.author.id', $user->id);
    }

    public function test_index_returns_20_per_page(): void
    {
        $user = User::factory()->create();

        Post::factory()->count(25)->create([
            'user_id' => $user->id,
            'is_draft' => false,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->getJson('/posts');

        $response->assertOk();
        $response->assertJsonPath('meta.per_page', 20);
        $response->assertJsonPath('meta.total', 25);
    }

    // =========================================================================
    // 4-2: posts.create
    // =========================================================================

    public function test_create_requires_authentication(): void
    {
        $response = $this->get('/posts/create');

        $response->assertRedirect('/login');
    }

    public function test_create_returns_string_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/posts/create');

        $response->assertOk();
        $response->assertSee('posts.create');
    }

    // =========================================================================
    // 4-3: posts.store
    // =========================================================================

    public function test_store_requires_authentication(): void
    {
        $response = $this->postJson('/posts', [
            'title' => 'Test Post',
            'content' => 'Test content',
        ]);

        $response->assertUnauthorized();
    }

    public function test_store_creates_post_and_returns_201(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/posts', [
            'title' => 'My New Post',
            'content' => 'This is the content of my post.',
            'published_at' => now()->toDateTimeString(),
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.title', 'My New Post');
        $response->assertJsonStructure([
            'data' => ['id', 'title', 'content', 'is_draft', 'published_at', 'author'],
        ]);
        $this->assertDatabaseHas('posts', [
            'title' => 'My New Post',
            'content' => 'This is the content of my post.',
            'user_id' => $user->id,
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/posts', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['title', 'content']);
    }

    public function test_store_assigns_authenticated_user_as_author(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/posts', [
            'title' => 'Authored Post',
            'content' => 'Content here.',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.author.id', $user->id);
        $this->assertDatabaseHas('posts', [
            'title' => 'Authored Post',
            'user_id' => $user->id,
        ]);
    }

    // =========================================================================
    // 4-4: posts.show
    // =========================================================================

    public function test_show_returns_published_post_as_json(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => false,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->getJson("/posts/{$post->id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', $post->id);
        $response->assertJsonPath('data.title', $post->title);
        $response->assertJsonStructure([
            'data' => ['id', 'title', 'content', 'is_draft', 'published_at', 'author'],
        ]);
    }

    public function test_show_returns_404_for_draft_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => true,
            'published_at' => null,
        ]);

        $response = $this->getJson("/posts/{$post->id}");

        $response->assertNotFound();
    }

    public function test_show_returns_404_for_scheduled_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => false,
            'published_at' => now()->addDays(5),
        ]);

        $response = $this->getJson("/posts/{$post->id}");

        $response->assertNotFound();
    }

    public function test_show_includes_author_data(): void
    {
        $user = User::factory()->create(['name' => 'Jane Doe']);
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => false,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->getJson("/posts/{$post->id}");

        $response->assertOk();
        $response->assertJsonPath('data.author.name', 'Jane Doe');
        $response->assertJsonPath('data.author.id', $user->id);
    }

    // =========================================================================
    // 4-5: posts.edit
    // =========================================================================

    public function test_edit_requires_authentication(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->get("/posts/{$post->id}/edit");

        $response->assertRedirect('/login');
    }

    public function test_edit_returns_string_for_author(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get("/posts/{$post->id}/edit");

        $response->assertOk();
        $response->assertSee('posts.edit');
    }

    public function test_edit_forbidden_for_non_author(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        $response = $this->actingAs($otherUser)->get("/posts/{$post->id}/edit");

        $response->assertForbidden();
    }

    // =========================================================================
    // 4-6: posts.update
    // =========================================================================

    public function test_update_requires_authentication(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->putJson("/posts/{$post->id}", [
            'title' => 'Updated Title',
        ]);

        $response->assertUnauthorized();
    }

    public function test_update_returns_updated_resource_for_author(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->putJson("/posts/{$post->id}", [
            'title' => 'Updated Title',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.title', 'Updated Title');
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_update_forbidden_for_non_author(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        $response = $this->actingAs($otherUser)->putJson("/posts/{$post->id}", [
            'title' => 'Hijacked Title',
        ]);

        $response->assertForbidden();
    }

    public function test_update_validates_data(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->putJson("/posts/{$post->id}", [
            'title' => '', // required when present
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['title']);
    }

    // =========================================================================
    // 4-7: posts.destroy
    // =========================================================================

    public function test_destroy_requires_authentication(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->deleteJson("/posts/{$post->id}");

        $response->assertUnauthorized();
    }

    public function test_destroy_returns_no_content_for_author(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->deleteJson("/posts/{$post->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_destroy_forbidden_for_non_author(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        $response = $this->actingAs($otherUser)->deleteJson("/posts/{$post->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }
}
