<?php

require_once 'PostModel.php';
require_once 'VideoModel.php';
require_once 'PolymorphicCommentModel.php';
require_once 'PolymorphicThumbnailModel.php';

use Lightpack\Container\Container;
use Lightpack\Database\Lucid\Collection;
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
            'morph_id' => $postId,
            'morph_type' => 'posts',
        ]);
        $commentPostId = $this->db->lastInsertId();

        $this->db->table('polymorphic_comments')->insert([
            'body' => 'Video comment',
            'morph_id' => $videoId,
            'morph_type' => 'videos',
        ]);
        $commentVideoId = $this->db->lastInsertId();

        // Test morphTo for post comment
        $comment = $this->db->model(PolymorphicCommentModel::class)->find($commentPostId);
        $commentablePost = $comment->commentable();
        $this->assertInstanceOf(PostModel::class, $commentablePost);
        $this->assertEquals('A Post', $commentablePost->title);

        // Test morphTo for video comment
        $comment = $this->db->model(PolymorphicCommentModel::class)->find($commentVideoId);
        $commentableVideo = $comment->commentable();
        $this->assertInstanceOf(VideoModel::class, $commentableVideo);
        $this->assertEquals('A Video', $commentableVideo->title);
    }

    public function testLoadMorphsBatchesMorphParents()
    {
        // Create post and video
        $this->db->table('posts')->insert(['title' => 'A Post']);
        $postId = $this->db->lastInsertId();

        $this->db->table('videos')->insert(['title' => 'A Video']);
        $videoId = $this->db->lastInsertId();

        // Create comments for both
        $this->db->table('polymorphic_comments')->insert([
            [
                'body' => 'Post comment',
                'morph_id' => $postId,
                'morph_type' => 'posts',
            ],
            [
                'body' => 'Video comment',
                'morph_id' => $videoId,
                'morph_type' => 'videos',
            ]
        ]);

        // Use loadMorphs to batch-load parents for all comments
        $comments = PolymorphicCommentModel::query()->all();
        $comments->loadMorphs([
            PostModel::class,
            VideoModel::class,
        ]);

        // Should return a Collection with both comments
        $this->assertCount(2, $comments);

        // Check that each comment has a loaded parent of the correct type and title
        foreach ($comments as $comment) {
            $this->assertNotNull($comment->parent);
            if ($comment->morph_type === 'posts') {
                $this->assertInstanceOf(PostModel::class, $comment->parent);
                $this->assertEquals('A Post', $comment->parent->title);
            } elseif ($comment->morph_type === 'videos') {
                $this->assertInstanceOf(VideoModel::class, $comment->parent);
                $this->assertEquals('A Video', $comment->parent->title);
            } else {
                $this->fail('Unknown morph_type: ' . $comment->morph_type);
            }
        }
    }

    public function testLoadMorphsOnNonPolymorphicModels()
    {
        $this->db->table('posts')->insert(['title' => 'A Post']);
        $this->db->table('posts')->insert(['title' => 'Another Post']);

        $posts = $this->db->model(PostModel::class)::query()->all();
        $this->assertEquals(2, $posts->count());

        // should silently skip loading morphed parent
        $posts->loadMorphs([VideoModel::class]);
        $this->assertNull($posts[0]->parent);
        $this->assertNull($posts[1]->parent);
    }

    public function testMorphManyRelation()
    {
        $this->db->table('posts')->insert(['title' => 'A Post']);
        $postId = $this->db->lastInsertId();

        $videoId = $this->db->table('videos')->insert(['title' => 'A Video']);
        $videoId = $this->db->lastInsertId();

        // Insert comments
        $this->db->table('polymorphic_comments')->insert([
            ['body' => 'First post comment', 'morph_id' => $postId, 'morph_type' => 'posts'],
            ['body' => 'Second post comment', 'morph_id' => $postId, 'morph_type' => 'posts'],
            ['body' => 'Video comment', 'morph_id' => $videoId, 'morph_type' => 'videos'],
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
            'url' => 'post-thumb.jpg',
            'morph_id' => $postId,
            'morph_type' => 'posts',
        ]);
        $this->db->table('polymorphic_thumbnails')->insert([
            'url' => 'video-thumb.jpg',
            'morph_id' => $videoId,
            'morph_type' => 'videos',
        ]);

        $post = $this->db->model(PostModel::class)->find($postId);
        $thumbnail = $post->thumbnail;
        $this->assertNotNull($thumbnail);
        $this->assertEquals('post-thumb.jpg', $thumbnail->url);

        $video = $this->db->model(VideoModel::class)->find($videoId);
        $thumbnail = $video->thumbnail;
        $this->assertNotNull($thumbnail);
        $this->assertEquals('video-thumb.jpg', $thumbnail->url);
    }

    public function testEagerLoadingPolymorphicRelations()
    {
        $this->db->table('posts')->insert(['title' => 'A Post']);
        $postId = $this->db->lastInsertId();
        $this->db->table('videos')->insert(['title' => 'A Video']);
        $videoId = $this->db->lastInsertId();

        $this->db->table('polymorphic_comments')->insert([
            ['body' => 'First post comment', 'morph_id' => $postId, 'morph_type' => 'posts'],
            ['body' => 'Second post comment', 'morph_id' => $postId, 'morph_type' => 'posts'],
            ['body' => 'Video comment', 'morph_id' => $videoId, 'morph_type' => 'videos'],
        ]);
        $this->db->table('polymorphic_thumbnails')->insert([
            ['url' => 'post-thumb.jpg', 'morph_id' => $postId, 'morph_type' => 'posts'],
            ['url' => 'video-thumb.jpg', 'morph_id' => $videoId, 'morph_type' => 'videos'],
        ]);

        $posts = $this->db->model(PostModel::class)::query()->with('comments', 'thumbnail')->all();
        $this->assertCount(1, $posts);
        $this->assertCount(2, $posts[0]->comments);
        $this->assertEquals('post-thumb.jpg', $posts[0]->thumbnail->url);

        $videos = $this->db->model(VideoModel::class)::query()->with('comments', 'thumbnail')->all();
        $this->assertCount(1, $videos);
        $this->assertCount(1, $videos[0]->comments);
        $this->assertEquals('video-thumb.jpg', $videos[0]->thumbnail->url);
    }

    public function testPolymorphicRelationCounts()
    {
        $this->db->table('posts')->insert(['title' => 'A Post']);
        $postId = $this->db->lastInsertId();
        $this->db->table('videos')->insert(['title' => 'A Video']);
        $videoId = $this->db->lastInsertId();

        $this->db->table('polymorphic_comments')->insert([
            ['body' => 'First post comment', 'morph_id' => $postId, 'morph_type' => 'posts'],
            ['body' => 'Second post comment', 'morph_id' => $postId, 'morph_type' => 'posts'],
            ['body' => 'Video comment', 'morph_id' => $videoId, 'morph_type' => 'videos'],
        ]);

        $posts = $this->db->model(PostModel::class)::query()->withCount('comments')->all();
        $this->assertEquals(2, $posts[0]->comments_count);

        $videos = $this->db->model(VideoModel::class)::query()->withCount('comments')->all();
        $this->assertEquals(1, $videos[0]->comments_count);
    }

    public function testPolymorphicRelationQueryChaining()
    {
        $this->db->table('posts')->insert(['title' => 'A Post']);
        $postId = $this->db->lastInsertId();

        $this->db->table('polymorphic_comments')->insert([
            ['body' => 'First post comment', 'morph_id' => $postId, 'morph_type' => 'posts'],
            ['body' => 'Second post comment', 'morph_id' => $postId, 'morph_type' => 'posts'],
        ]);

        $post = $this->db->model(PostModel::class)->find($postId);
        $comments = $post->comments()->where('body', 'LIKE', '%Second%')->all();
        $this->assertCount(1, $comments);
        $this->assertEquals('Second post comment', $comments[0]->body);
    }

    public function testPolymorphicEdgeCases()
    {
        $this->db->table('posts')->insert(['title' => 'A Post']);
        $postId = $this->db->lastInsertId();

        $post = $this->db->model(PostModel::class)->find($postId);
        $this->assertEmpty($post->comments);
        $this->assertNull($post->thumbnail);

        $this->db->table('polymorphic_thumbnails')->insert([
            ['url' => 'A', 'morph_id' => $postId, 'morph_type' => 'posts'],
            ['url' => 'B', 'morph_id' => $postId, 'morph_type' => 'posts'],
        ]);
        $post = $this->db->model(PostModel::class)->find($postId);
        $this->assertNotNull($post->thumbnail);
        $this->assertContains($post->thumbnail->url, ['A', 'B']);
    }

    public function testDeferredPolymorphicRelationLoading()
    {
        $this->db->table('posts')->insert(['title' => 'A Post']);
        $postId = $this->db->lastInsertId();

        $this->db->table('polymorphic_comments')->insert([
            ['body' => 'First post comment', 'morph_id' => $postId, 'morph_type' => 'posts'],
            ['body' => 'Second post comment', 'morph_id' => $postId, 'morph_type' => 'posts'],
        ]);

        // Retrieve post without eager loading
        $post = $this->db->model(PostModel::class)->find($postId);

        // Relation should load only when accessed
        $this->assertFalse($post->hasAttribute('comments'), 'Comments should not be loaded yet.');
        $comments = $post->comments;
        $this->assertCount(2, $comments);
        $this->assertEquals('First post comment', $comments[0]->body);
        $this->assertEquals('Second post comment', $comments[1]->body);
    }

    public function testDeferredPolymorphicRelationCount()
    {
        $this->db->table('posts')->insert(['title' => 'A Post']);
        $postId = $this->db->lastInsertId();

        $this->db->table('polymorphic_comments')->insert([
            ['body' => 'First post comment', 'morph_id' => $postId, 'morph_type' => 'posts'],
            ['body' => 'Second post comment', 'morph_id' => $postId, 'morph_type' => 'posts'],
        ]);

        $post = $this->db->model(PostModel::class)->find($postId);

        // Count comments via relation query
        $count = $post->comments()->count();
        $this->assertEquals(2, $count);

        // Add another comment and check count again
        $this->db->table('polymorphic_comments')->insert([
            'body' => 'Third post comment',
            'morph_id' => $postId,
            'morph_type' => 'posts'
        ]);
        $count = $post->comments()->count();
        $this->assertEquals(3, $count);
    }

    public function testDeferredPolymorphicCollectionLoad()
    {
        $this->db->table('posts')->insert(['title' => 'A Post']);
        $postId = $this->db->lastInsertId();

        $this->db->table('videos')->insert(['title' => 'A Video']);
        $videoId = $this->db->lastInsertId();

        $this->db->table('polymorphic_comments')->insert([
            ['body' => 'First post comment', 'morph_id' => $postId, 'morph_type' => 'posts'],
            ['body' => 'Second post comment', 'morph_id' => $postId, 'morph_type' => 'posts'],
            ['body' => 'Video comment', 'morph_id' => $videoId, 'morph_type' => 'videos'],
        ]);

        $posts = $this->db->model(PostModel::class)::query()->all();

        // Now load comments for all posts
        $posts->load('comments');

        foreach ($posts as $post) {
            if ($post->title === 'A Post') {
                $this->assertCount(2, $post->comments);
            }
        }
    }

    public function testDeferredPolymorphicCollectionLoadCount()
    {
        $this->db->table('posts')->insert(['title' => 'A Post']);
        $postId = $this->db->lastInsertId();

        $this->db->table('videos')->insert(['title' => 'A Video']);
        $videoId = $this->db->lastInsertId();

        $this->db->table('polymorphic_comments')->insert([
            ['body' => 'First post comment', 'morph_id' => $postId, 'morph_type' => 'posts'],
            ['body' => 'Second post comment', 'morph_id' => $postId, 'morph_type' => 'posts'],
            ['body' => 'Video comment', 'morph_id' => $videoId, 'morph_type' => 'videos'],
        ]);

        $posts = $this->db->model(PostModel::class)::query()->all();

        // Now load comments count for all posts
        $posts->loadCount('comments');

        foreach ($posts as $post) {
            $this->assertEquals(2, $post->comments_count);
        }
    }
}
