<?php
namespace Weave\Controllers;
use Weave\Models\Post;
use Weave\Models\Comment;
use Lacebox\Sole\RequestValidator;
class PostController {
    use BaseController;
    public function index() {
        $posts = Post::query()->with('comments')->get();
        return $this->respond($posts);
    }
    public function show($id) {
        $post = Post::query()->findOrFail($id);
        return $this->respond($post);
    }
    public function store() {
        RequestValidator::getInstance()->setRules([
            'title' => 'required|string',
            'body' => 'required|string',
            'user_id' => 'required|integer',
        ])->validate();

        $post = Post::create(request()->all());
        return $this->respond($post, 201);
    }
    public function update($id) {
        $post = Post::query()->findOrFail($id);

        RequestValidator::getInstance()->setRules([
            'title' => 'string',
            'body' => 'string',
            'user_id' => 'integer',
        ])->validate();

        $post->update(request()->all());
        return $this->respond($post);
    }
    public function destroy($id) {
        $post = Post::query()->findOrFail($id);
        $post->delete();
        return $this->respond(null, 204);
    }
}