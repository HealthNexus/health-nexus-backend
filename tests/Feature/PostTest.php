<?php

use App\Models\Post;
use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);



it('can retrieve all posts')->get('/api/posts')->assertStatus(200);



it('can retrieve a single post', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();
    Sanctum::actingAs($user);
    $response = $this->getJson("/api/posts/{$post->id}");

    $response->assertStatus(200);
    $response->assertJson([
        'post'=>[
            'id' => $post->id,
            'title'=>$post->title,
            'excerpt'=>$post->excerpt,
            'body'=>$post->body
            ]
    ]);
});

it('can create a post', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $postData = [
        'title' => 'New Post',
        'excerpt' => 'Content of the new post',
        'body' => 'Some nice body for my nice post'
    ];

    $response = $this->postJson('/api/posts/create', $postData);

    $response->assertStatus(200);
    $this->assertDatabaseHas('posts', $postData);
});

it('can update a post', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $post = Post::factory()->create();
    $updatedData = [
        'title' => 'Updated Title',
        'excerpt' => 'Updated Excerpt',
    ];

    $response = $this->putJson("/api/posts/{$post->id}/update", $updatedData);

    $response->assertStatus(200);
    $this->assertDatabaseHas('posts', $updatedData);
});

it('can delete a post', function () {
    $post = Post::factory()->create();
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->deleteJson("/api/posts/{$post->id}/destroy");

    $response->assertStatus(204);
    $this->assertDatabaseMissing('posts', ['id' => $post->id]);
});
