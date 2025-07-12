<?php
namespace Shoebox\Migrations;
use Lacebox\Sole\Cobble\Welt;
class CreatePostsTable {
    public function up(): void {
        Welt::create('posts', function ($bp) {
            $bp->increments('id');
            $bp->string('title');
            $bp->text('body');
            $bp->integer('user_id');
            $bp->timestamps();
        });
    }

    public function down(): void {
        Welt::dropIfExists('posts');
    }
}