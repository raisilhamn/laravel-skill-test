<?php

namespace Tests\Unit;

use App\Models\Post;
use App\Models\User;
use App\Policies\PostPolicy;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Model Relationships
    // =========================================================================

    public function test_post_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $post->user);
        $this->assertEquals($user->id, $post->user->id);
    }

    // =========================================================================
    // scopePublished
    // =========================================================================

    public function test_scope_published_returns_only_published_posts(): void
    {
        $user = User::factory()->create();

        // Published post
        $published = Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => false,
            'published_at' => now()->subDay(),
        ]);

        // Draft post
        Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => true,
            'published_at' => null,
        ]);

        // Scheduled post
        Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => false,
            'published_at' => now()->addDays(3),
        ]);

        $results = Post::published()->get();

        $this->assertCount(1, $results);
        $this->assertEquals($published->id, $results->first()->id);
    }

    public function test_scope_published_excludes_drafts_even_with_past_published_at(): void
    {
        $user = User::factory()->create();

        Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => true,
            'published_at' => now()->subDay(), // past date, but still a draft
        ]);

        $results = Post::published()->get();

        $this->assertCount(0, $results);
    }

    // =========================================================================
    // isPublished()
    // =========================================================================

    public function test_is_published_returns_true_for_active_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => false,
            'published_at' => now()->subHour(),
        ]);

        $this->assertTrue($post->isPublished());
    }

    public function test_is_published_returns_false_for_draft(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => true,
            'published_at' => null,
        ]);

        $this->assertFalse($post->isPublished());
    }

    public function test_is_published_returns_false_for_scheduled(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => false,
            'published_at' => now()->addDays(2),
        ]);

        $this->assertFalse($post->isPublished());
    }

    public function test_is_published_returns_false_for_null_published_at(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => false,
            'published_at' => null,
        ]);

        $this->assertFalse($post->isPublished());
    }

    // =========================================================================
    // Casts
    // =========================================================================

    public function test_published_at_is_cast_to_carbon(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'published_at' => '2026-01-01 12:00:00',
        ]);

        $this->assertInstanceOf(Carbon::class, $post->published_at);
    }

    public function test_is_draft_is_cast_to_boolean(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'is_draft' => 1,
        ]);

        $this->assertIsBool($post->is_draft);
        $this->assertTrue($post->is_draft);
    }

    // =========================================================================
    // Fillable
    // =========================================================================

    public function test_fillable_fields(): void
    {
        $post = new Post;
        $expected = ['title', 'content', 'is_draft', 'published_at'];

        $this->assertEquals($expected, $post->getFillable());
    }

    // =========================================================================
    // PostPolicy
    // =========================================================================

    public function test_policy_allows_author_to_update(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);
        $policy = new PostPolicy;

        $this->assertTrue($policy->update($user, $post));
    }

    public function test_policy_denies_non_author_to_update(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);
        $policy = new PostPolicy;

        $this->assertFalse($policy->update($otherUser, $post));
    }

    public function test_policy_allows_author_to_delete(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);
        $policy = new PostPolicy;

        $this->assertTrue($policy->delete($user, $post));
    }

    public function test_policy_denies_non_author_to_delete(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);
        $policy = new PostPolicy;

        $this->assertFalse($policy->delete($otherUser, $post));
    }
}
