<?php

namespace Post;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('fake_storage');
    }

    public function test_a_post_can_be_stored()
    {
        $this->withoutExceptionHandling();

        $file = File::create('my_image.jpg');

        $data = [
            'title' => 'Some title',
            'description' => 'Description',
            'image' => $file,
        ];


        $response = $this->post('/posts', $data);

        $response->assertOk();

        $this->assertDatabaseCount('posts', 1);

        $post = Post::first();

        $this->assertEquals($data['title'], $post->title);
        $this->assertEquals($data['description'], $post->description);
        $this->assertEquals('images/' . $file->hashName(), $post->image_url);

        Storage::disk('fake_storage')->assertExists('images/' . $file->hashName());
    }

    public function test_attribute_title_is_required_for_storing_post()
    {
        $data = [];

        $response = $this->post('/posts', $data);

        $response
            ->assertRedirect()
            ->assertInvalid('title');
    }

    public function test_attribute_image_is_file_for_storing_post()
    {
        $data = [
            'image' => 'not file',
        ];

        $response = $this->post('/posts', $data);

        $response
            ->assertRedirect()
            ->assertInvalid('image');
    }

    public function test_a_post_can_be_updated()
    {
        $this->withoutExceptionHandling();

        $post = Post::factory()->create();

        $file = File::create('image.jpg');

        $data = [
            'title' => 'Some edited title',
            'description' => 'Edited description',
            'image' => $file,
        ];

        $response = $this->patch('/posts/' . $post->id, $data);

        $response->assertOk();

        $updatedPost = Post::first();

        $this->assertEquals($data['title'], $updatedPost->title);
        $this->assertEquals($data['description'], $updatedPost->description);
        $this->assertEquals('images/' . $file->hashName(), $updatedPost->image_url);
        $this->assertEquals($post->id, $updatedPost->id);
    }

    public function test_response_for_route_posts_index_is_view_post_index_with_posts()
    {
        $this->withoutExceptionHandling();

        $posts = Post::factory(10)->create();

        $titles = $posts->pluck('title')->toArray();

        $response = $this->get('/posts');

        $response
            ->assertViewIs('post.index')
            ->assertSeeText($titles);
    }

    public function test_response_for_route_posts_show_is_view_post_show_with_single_post()
    {
        $this->withoutExceptionHandling();

        $post = Post::factory()->create();

        $response = $this->get('/posts/' . $post->id);

        $response
            ->assertViewIs('post.show')
            ->assertSeeText($post->title);
    }

    public function test_a_post_can_be_deleted()
    {
        $this->withoutExceptionHandling();

        $post = Post::factory()->create();
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/posts/' . $post->id);

        $response->assertOk();

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_a_post_can_be_deleted_by_auth_user_only()
    {
        $post = Post::factory()->create();

        $response = $this->delete('/posts/' . $post->id);

        $response->assertRedirect();

        $this->assertDatabaseCount('posts', 1);
    }
}
