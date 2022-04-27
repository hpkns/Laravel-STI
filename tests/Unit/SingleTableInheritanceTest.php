<?php

namespace Tests\Unit;

use Tests\Fakes\Comment;
use Tests\Fakes\Content;
use Tests\Fakes\Page;
use Tests\Fakes\Post;
use Tests\Fakes\User;
use Tests\TestCase;

class SingleTableInheritanceTest extends TestCase
{
    public function test_new_models_have_type_set_on_creation()
    {
        $page = new Page(['title' => 'tests']);

        $this->assertEquals('page', $page->type);
    }

    /**
     * @covers \Hpkns\Laravel\Sti\SingleTableInheritance::initializeSingleTableInheritance()
     */
    public function test_type_is_well_set_on_creation()
    {
        $page = Page::factory()->create([]);

        $this->assertEquals('page', $page->fresh()->type);
    }

    public function test_updateOrCreate_from_root_class()
    {
        $post = Content::updateOrCreate(['title' => 'test'], ['type' => 'post']);

        $this->assertInstanceOf(Post::class, $post);
        $this->assertTrue($post->wasRecentlyCreated);
    }

    public function test_updateOrCreate_when_model_exists($key = ['title' => 'test'])
    {
        Content::factory()->create($key + ['type' => 'post']);

        $post = Content::updateOrCreate($key, ['type' => 'post']);

        $this->assertInstanceOf(Post::class, $post);
        $this->assertFalse($post->wasRecentlyCreated);
    }

    public function test_updateOrCreate_from_child_class()
    {
        $post = Post::updateOrCreate(['title' => 'test']);

        $this->assertInstanceOf(Post::class, $post);
        $this->assertTrue($post->wasRecentlyCreated);
    }

    public function test_updateOrCreate_from_child_class_when_model_exists($key = ['title' => 'test'])
    {
        Content::factory()->create($key + ['type' => 'post']);

        $post = Post::updateOrCreate($key);

        $this->assertInstanceOf(Post::class, $post);
        $this->assertFalse($post->wasRecentlyCreated);
    }

    public function test_scope_narrows_to_children_models($posts = 11, $pages = 13)
    {
        Post::factory($posts)->create();
        Page::factory($pages)->create();

        $this->assertEquals($posts + $pages, Content::count());
        $this->assertEquals($posts, Post::count());
        $this->assertEquals($pages, Page::count());
    }

    public function test_can_disable_global_scope_although_i_dont_see_why_you_would_want_to_do_that($posts = 11, $pages = 13)
    {
        Post::factory($posts)->create();
        Page::factory($pages)->create();

        $this->assertEquals($total = $posts + $pages, Content::count());
        $this->assertEquals($total, Post::withoutSti()->count());
        $this->assertEquals($total, Page::withoutSti()->count());
    }

    /**
     * @covers \Hpkns\Laravel\Sti\SingleTableInheritance::getForeignKey()
     */
    public function test_relations_work_with_proper_foreign_key($count = 10)
    {
        $post = Post::factory()->has(Comment::factory()->count($count))->create();

        $post->fresh();

        $this->assertCount($count, $post->comments);
    }
}
