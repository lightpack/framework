<?php

require_once 'PostModel.php';
require_once 'VideoModel.php';
require_once 'PolymorphicCommentModel.php';
require_once 'PolymorphicThumbnailModel.php';

use Lightpack\Container\Container;
use PHPUnit\Framework\TestCase;

class ModelPolymorphicTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        $config = require __DIR__ . '/../tmp/mysql.config.php';
        $this->db = new \Lightpack\Database\Adapters\Mysql($config);
        $sql = file_get_contents(__DIR__ . '/../tmp/db.sql');
        $stmt = $this->db->query($sql);
        $stmt->closeCursor();

        // Configure container
        $container = Container::getInstance();
        $container->register('db', function () {
            return $this->db;
        });
        $container->register('logger', function () {
            return new class {
                public function error($message, $context = []) {}
                public function critical($message, $context = []) {}
            };
        });
    }

    public function tearDown(): void
    {
        $sql = "DROP TABLE IF EXISTS polymorphic_comments, posts, videos";
        $this->db->query($sql);
        $this->db = null;
    }

    public function testMorphToRelation()
    {
        // Create post and video
        $this->db->table('posts')->insert(['title' => 'A Post']);
        $postId = $this->db->lastInsertId();

        $this->db->table('videos')->insert(['title' => 'A Video']);
        $videoId = $this->db->lastInsertId();

        // Create comments for both
        $this->db->table('polymorphic_comments')->insert([
            'body' => 'Post comment',
            'commentable_id' => $postId,
            'commentable_type' => 'post',
        ]);
        $commentPostId = $this->db->lastInsertId();

        $this->db->table('polymorphic_comments')->insert([
            'body' => 'Video comment',
            'commentable_id' => $videoId,
            'commentable_type' => 'video',
        ]);
        $commentVideoId = $this->db->lastInsertId();

        // Test morphTo for post comment
        $comment = $this->db->model(PolymorphicCommentModel::class)->find($commentPostId);
        $commentable = $comment->commentable();
        $this->assertInstanceOf(PostModel::class, $commentable);
        $this->assertEquals('A Post', $commentable->title);

        // Test morphTo for video comment
        $comment = $this->db->model(PolymorphicCommentModel::class)->find($commentVideoId);
        $commentable = $comment->commentable();
        $this->assertInstanceOf(VideoModel::class, $commentable);
        $this->assertEquals('A Video', $commentable->title);
    }

    public function testMorphManyRelation()
    {
        $this->db->table('posts')->insert(['title' => 'A Post']);
        $postId = $this->db->lastInsertId();

        $videoId = $this->db->table('videos')->insert(['title' => 'A Video']);
        $videoId = $this->db->lastInsertId();

        // Insert comments
        $this->db->table('polymorphic_comments')->insert([
            ['body' => 'First post comment', 'commentable_id' => $postId, 'commentable_type' => 'post'],
            ['body' => 'Second post comment', 'commentable_id' => $postId, 'commentable_type' => 'post'],
            ['body' => 'Video comment', 'commentable_id' => $videoId, 'commentable_type' => 'video'],
        ]);

        // Test morphMany for post
        $post = $this->db->model(PostModel::class)->find($postId);
        $comments = $post->comments;
        $this->assertCount(2, $comments);
        $this->assertEquals('First post comment', $comments[0]->body);
        $this->assertEquals('Second post comment', $comments[1]->body);

        // Test morphMany for video
        $video = $this->db->model(VideoModel::class)->find($videoId);
        $comments = $video->comments;
        $this->assertCount(1, $comments);
        $this->assertEquals('Video comment', $comments[0]->body);
    }

    public function testMorphOneThumbnail()
    {
        $this->db->table('posts')->insert(['title' => 'A Post']);
        $postId = $this->db->lastInsertId();

        $this->db->table('videos')->insert(['title' => 'A Video']);
        $videoId = $this->db->lastInsertId();

        $this->db->table('polymorphic_thumbnails')->insert([
            'url' => 'http://example.com/post-thumb.jpg',
            'thumbnailable_id' => $postId,
            'thumbnailable_type' => 'post',
        ]);
        $this->db->table('polymorphic_thumbnails')->insert([
            'url' => 'http://example.com/video-thumb.jpg',
            'thumbnailable_id' => $videoId,
            'thumbnailable_type' => 'video',
        ]);

        $post = $this->db->model(PostModel::class)->find($postId);
        $thumbnail = $post->thumbnail;
        $this->assertNotNull($thumbnail);
        $this->assertEquals('http://example.com/post-thumb.jpg', $thumbnail->url);

        $video = $this->db->model(VideoModel::class)->find($videoId);
        $thumbnail = $video->thumbnail;
        $this->assertNotNull($thumbnail);
        $this->assertEquals('http://example.com/video-thumb.jpg', $thumbnail->url);
    }
}
