<?php

namespace Tests\Unit;

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
     * @uses \Hpkns\Laravel\Sti\SingleTableInheritance::initializeSingleTableInheritance()
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
}
