<?php

namespace Post;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApiPostTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('fake_storage');
        $this->withHeaders([
            'accept' => 'application/json',
        ]);
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

        $response = $this->post('/api/posts', $data);

        $this->assertDatabaseCount('posts', 1);

        $post = Post::first();

        $this->assertEquals($data['title'], $post->title);
        $this->assertEquals($data['description'], $post->description);
        $this->assertEquals('images/' . $file->hashName(), $post->image_url);

        Storage::disk('fake_storage')->assertExists('images/' . $file->hashName());

        $response
            ->assertJson([
                'id' => $post->id,
                'title' => $post->title,
                'description' => $post->description,
                'image_url' => $post->image_url,
                'created_at' => $post->created_at->format('Y-m-d'),
                'updated_at' => $post->updated_at->format('Y-m-d'),
            ]);
    }

    public function test_attribute_title_is_required_for_storing_post()
    {
        $data = [];

        $response = $this->post('/api/posts', $data);

        $response
            ->assertStatus(422)
            ->assertInvalid('title');
    }

    public function test_attribute_image_is_file_for_storing_post()
    {
        $data = [
            'image' => 'not file',
        ];

        $response = $this->post('/api/posts', $data);

        $response
            ->assertStatus(422)
            ->assertInvalid('image')
            ->assertJsonValidationErrors([
                'image' => 'The image field must be a file.'
            ]);
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

        $response = $this->patch('/api/posts/' . $post->id, $data);

        $updatedPost = Post::first();

        $this->assertEquals($data['title'], $updatedPost->title);
        $this->assertEquals($data['description'], $updatedPost->description);
        $this->assertEquals('images/' . $file->hashName(), $updatedPost->image_url);
        $this->assertEquals($post->id, $updatedPost->id);

        $response
            ->assertJson([
                'id' => $post->id,
                'title' => $data['title'],
                'description' => $data['description'],
                'image_url' => 'images/' . $file->hashName(),
            ]);
    }

    public function test_response_for_route_posts_index_is_json_with_posts()
    {
        $this->withoutExceptionHandling();

        $posts = Post::factory(10)->create();

        $response = $this->get('/api/posts');

        $json = $posts->map(function ($post) {
            return [
                'id' => $post->id,
                'title' => $post->title,
                'description' => $post->description,
                'image_url' => $post->image_url,
                'created_at' => $post->created_at->format('Y-m-d'),
                'updated_at' => $post->updated_at->format('Y-m-d'),
            ];
        })->toArray();

        // Строгое сравнение Json в response
        $response->assertExactJson($json);
    }

    public function test_response_for_route_posts_show_is_json_with_single_post()
    {
        $this->withoutExceptionHandling();

        $post = Post::factory()->create();

        $response = $this->get('/api/posts/' . $post->id);

        $response
            ->assertJson([
                'id' => $post->id,
                'title' => $post->title,
                'description' => $post->description,
                'image_url' => $post->image_url,
            ]);
    }

    public function test_a_post_can_be_deleted()
    {
        $this->withoutExceptionHandling();

        $post = Post::factory()->create();
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/api/posts/' . $post->id);

        $response->assertJson([
            'message' => 'deleted',
        ]);

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_a_post_can_be_deleted_by_auth_user_only()
    {
        $post = Post::factory()->create();

        $response = $this->delete('/api/posts/' . $post->id);

        $response->assertUnauthorized();

        $this->assertDatabaseCount('posts', 1);
    }
}
