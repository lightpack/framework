<?php

require_once 'PostModel.php';
require_once 'VideoModel.php';
require_once 'PolymorphicCommentModel.php';
require_once 'PolymorphicThumbnailModel.php';
require_once 'TagModel.php';

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
                public function error($message, $context = [])
                {
                }

                public function critical($message, $context = [])
                {
                }
            };
        });
    }

    public function tearDown(): void
    {
        $sql = "DROP TABLE IF EXISTS polymorphic_comments, polymorphic_thumbnails, tag_morphs, posts, videos, tags";
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
        $commentablePost = $comment->parent;
        $this->assertInstanceOf(PostModel::class, $commentablePost);
        $this->assertEquals('A Post', $commentablePost->title);

        // Test morphTo for video comment
        $comment = $this->db->model(PolymorphicCommentModel::class)->find($commentVideoId);
        $commentableVideo = $comment->parent;
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
            ],
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

    public function testPolymorphicWithCountOrderBy()
    {
        $this->db->table('posts')->insert([
            ['title' => 'Post with few comments'],
            ['title' => 'Post with many comments'],
        ]);
        $postIds = [1, 2];

        $this->db->table('videos')->insert(['title' => 'A Video']);
        $videoId = $this->db->lastInsertId();

        $this->db->table('polymorphic_comments')->insert([
            ['body' => 'Post 1 comment', 'morph_id' => $postIds[0], 'morph_type' => 'posts'],
            ['body' => 'Post 2 comment 1', 'morph_id' => $postIds[1], 'morph_type' => 'posts'],
            ['body' => 'Post 2 comment 2', 'morph_id' => $postIds[1], 'morph_type' => 'posts'],
            ['body' => 'Post 2 comment 3', 'morph_id' => $postIds[1], 'morph_type' => 'posts'],
            ['body' => 'Video comment', 'morph_id' => $videoId, 'morph_type' => 'videos'],
        ]);

        // Ordering must respect morph_type=posts; video comment must NOT be counted
        $posts = $this->db->model(PostModel::class)::query()
            ->withCount('comments')
            ->orderBy('comments_count', 'desc')
            ->all();

        $this->assertEquals(2, $posts->count());
        $this->assertEquals('Post with many comments', $posts[0]->title);
        $this->assertEquals(3, $posts[0]->comments_count);
        $this->assertEquals('Post with few comments', $posts[1]->title);
        $this->assertEquals(1, $posts[1]->comments_count);
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
            'morph_type' => 'posts',
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

    public function testMorphToManyRelation()
    {
        // Create posts and videos
        $this->db->table('posts')->insert(['title' => 'First Post']);
        $postId1 = $this->db->lastInsertId();
        $this->db->table('posts')->insert(['title' => 'Second Post']);
        $postId2 = $this->db->lastInsertId();

        $this->db->table('videos')->insert(['title' => 'First Video']);
        $videoId = $this->db->lastInsertId();

        // Create tags
        $this->db->table('tags')->insert(['name' => 'PHP', 'slug' => 'php']);
        $tagId1 = $this->db->lastInsertId();
        $this->db->table('tags')->insert(['name' => 'Lightpack', 'slug' => 'lightpack']);
        $tagId2 = $this->db->lastInsertId();
        $this->db->table('tags')->insert(['name' => 'Tutorial', 'slug' => 'tutorial']);
        $tagId3 = $this->db->lastInsertId();

        // Attach tags to posts and videos
        $this->db->table('tag_morphs')->insert([
            ['tag_id' => $tagId1, 'morph_id' => $postId1, 'morph_type' => 'posts'],
            ['tag_id' => $tagId2, 'morph_id' => $postId1, 'morph_type' => 'posts'],
            ['tag_id' => $tagId1, 'morph_id' => $postId2, 'morph_type' => 'posts'],
            ['tag_id' => $tagId3, 'morph_id' => $videoId, 'morph_type' => 'videos'],
        ]);

        // Test morphToMany for first post
        $post = $this->db->model(PostModel::class)->find($postId1);
        $tags = $post->tags;
        $this->assertCount(2, $tags);
        $this->assertEquals('PHP', $tags[0]->name);
        $this->assertEquals('Lightpack', $tags[1]->name);

        // Test morphToMany for second post
        $post = $this->db->model(PostModel::class)->find($postId2);
        $tags = $post->tags;
        $this->assertCount(1, $tags);
        $this->assertEquals('PHP', $tags[0]->name);

        // Test morphToMany for video
        $video = $this->db->model(VideoModel::class)->find($videoId);
        $tags = $video->tags;
        $this->assertCount(1, $tags);
        $this->assertEquals('Tutorial', $tags[0]->name);
    }

    public function testMorphedByManyRelation()
    {
        // Create posts and videos
        $this->db->table('posts')->insert(['title' => 'First Post']);
        $postId1 = $this->db->lastInsertId();
        $this->db->table('posts')->insert(['title' => 'Second Post']);
        $postId2 = $this->db->lastInsertId();

        $this->db->table('videos')->insert(['title' => 'First Video']);
        $videoId = $this->db->lastInsertId();

        // Create tags
        $this->db->table('tags')->insert(['name' => 'PHP', 'slug' => 'php']);
        $tagId1 = $this->db->lastInsertId();
        $this->db->table('tags')->insert(['name' => 'Tutorial', 'slug' => 'tutorial']);
        $tagId2 = $this->db->lastInsertId();

        // Attach tags to posts and videos
        $this->db->table('tag_morphs')->insert([
            ['tag_id' => $tagId1, 'morph_id' => $postId1, 'morph_type' => 'posts'],
            ['tag_id' => $tagId1, 'morph_id' => $postId2, 'morph_type' => 'posts'],
            ['tag_id' => $tagId1, 'morph_id' => $videoId, 'morph_type' => 'videos'],
            ['tag_id' => $tagId2, 'morph_id' => $videoId, 'morph_type' => 'videos'],
        ]);

        // Test morphedByMany for PHP tag - should get 2 posts
        $tag = $this->db->model(TagModel::class)->find($tagId1);
        $posts = $tag->posts()->all();
        $this->assertCount(2, $posts);
        $this->assertEquals('First Post', $posts[0]->title);
        $this->assertEquals('Second Post', $posts[1]->title);

        // Test morphedByMany for PHP tag - should get 1 video
        $videos = $tag->videos()->all();
        $this->assertCount(1, $videos);
        $this->assertEquals('First Video', $videos[0]->title);

        // Test morphedByMany for Tutorial tag - should get 0 posts
        $tag = $this->db->model(TagModel::class)->find($tagId2);
        $posts = $tag->posts()->all();
        $this->assertCount(0, $posts);

        // Test morphedByMany for Tutorial tag - should get 1 video
        $videos = $tag->videos()->all();
        $this->assertCount(1, $videos);
        $this->assertEquals('First Video', $videos[0]->title);
    }

    public function testMorphToManyAttach()
    {
        // Create post
        $this->db->table('posts')->insert(['title' => 'Test Post']);
        $postId = $this->db->lastInsertId();

        // Create tags
        $this->db->table('tags')->insert(['name' => 'PHP', 'slug' => 'php']);
        $tagId1 = $this->db->lastInsertId();
        $this->db->table('tags')->insert(['name' => 'Lightpack', 'slug' => 'lightpack']);
        $tagId2 = $this->db->lastInsertId();

        // Attach tags using pivot attach
        $post = $this->db->model(PostModel::class)->find($postId);
        $post->tags()->attach([$tagId1, $tagId2]);

        // Verify tags were attached
        $tags = $post->tags;
        $this->assertCount(2, $tags);

        // Verify pivot records exist
        $pivotRecords = $this->db->table('tag_morphs')
            ->where('morph_id', $postId)
            ->where('morph_type', 'posts')
            ->all();
        $this->assertCount(2, $pivotRecords);
    }

    public function testMorphToManyDetach()
    {
        // Create post
        $this->db->table('posts')->insert(['title' => 'Test Post']);
        $postId = $this->db->lastInsertId();

        // Create tags
        $this->db->table('tags')->insert(['name' => 'PHP', 'slug' => 'php']);
        $tagId1 = $this->db->lastInsertId();
        $this->db->table('tags')->insert(['name' => 'Lightpack', 'slug' => 'lightpack']);
        $tagId2 = $this->db->lastInsertId();
        $this->db->table('tags')->insert(['name' => 'Tutorial', 'slug' => 'tutorial']);
        $tagId3 = $this->db->lastInsertId();

        // Attach all tags
        $this->db->table('tag_morphs')->insert([
            ['tag_id' => $tagId1, 'morph_id' => $postId, 'morph_type' => 'posts'],
            ['tag_id' => $tagId2, 'morph_id' => $postId, 'morph_type' => 'posts'],
            ['tag_id' => $tagId3, 'morph_id' => $postId, 'morph_type' => 'posts'],
        ]);

        // Detach one tag
        $post = $this->db->model(PostModel::class)->find($postId);
        $post->tags()->detach([$tagId2]);

        // Verify only 2 tags remain
        $tags = $post->tags;
        $this->assertCount(2, $tags);
        $this->assertEquals('PHP', $tags[0]->name);
        $this->assertEquals('Tutorial', $tags[1]->name);
    }

    public function testMorphToManySync()
    {
        // Create post
        $this->db->table('posts')->insert(['title' => 'Test Post']);
        $postId = $this->db->lastInsertId();

        // Create tags
        $this->db->table('tags')->insert(['name' => 'PHP', 'slug' => 'php']);
        $tagId1 = $this->db->lastInsertId();
        $this->db->table('tags')->insert(['name' => 'Lightpack', 'slug' => 'lightpack']);
        $tagId2 = $this->db->lastInsertId();
        $this->db->table('tags')->insert(['name' => 'Tutorial', 'slug' => 'tutorial']);
        $tagId3 = $this->db->lastInsertId();

        // Initially attach PHP and Lightpack
        $this->db->table('tag_morphs')->insert([
            ['tag_id' => $tagId1, 'morph_id' => $postId, 'morph_type' => 'posts'],
            ['tag_id' => $tagId2, 'morph_id' => $postId, 'morph_type' => 'posts'],
        ]);

        // Sync to Lightpack and Tutorial (should remove PHP, keep Lightpack, add Tutorial)
        $post = $this->db->model(PostModel::class)->find($postId);
        $post->tags()->sync([$tagId2, $tagId3]);

        // Verify correct tags remain
        $tags = $post->tags;
        $this->assertCount(2, $tags);
        $this->assertEquals('Lightpack', $tags[0]->name);
        $this->assertEquals('Tutorial', $tags[1]->name);
    }

    public function testMorphToManyTypeIsolation()
    {
        // Create post and video
        $this->db->table('posts')->insert(['title' => 'Test Post']);
        $postId = $this->db->lastInsertId();
        $this->db->table('videos')->insert(['title' => 'Test Video']);
        $videoId = $this->db->lastInsertId();

        // Create tag
        $this->db->table('tags')->insert(['name' => 'PHP', 'slug' => 'php']);
        $tagId = $this->db->lastInsertId();

        // Attach tag to both post and video
        $this->db->table('tag_morphs')->insert([
            ['tag_id' => $tagId, 'morph_id' => $postId, 'morph_type' => 'posts'],
            ['tag_id' => $tagId, 'morph_id' => $videoId, 'morph_type' => 'videos'],
        ]);

        // Verify post only sees its own tag relationship
        $post = $this->db->model(PostModel::class)->find($postId);
        $tags = $post->tags;
        $this->assertCount(1, $tags);

        // Verify video only sees its own tag relationship
        $video = $this->db->model(VideoModel::class)->find($videoId);
        $tags = $video->tags;
        $this->assertCount(1, $tags);

        // Detach from post should not affect video
        $post->tags()->detach([$tagId]);
        $post = $this->db->model(PostModel::class)->find($postId);
        $tags = $post->tags;
        $this->assertCount(0, $tags);

        $video = $this->db->model(VideoModel::class)->find($videoId);
        $tags = $video->tags;
        $this->assertCount(1, $tags);
    }

    public function testMorphedByManyTypeIsolation()
    {
        // Create posts and videos
        $this->db->table('posts')->insert(['title' => 'First Post']);
        $postId1 = $this->db->lastInsertId();
        $this->db->table('posts')->insert(['title' => 'Second Post']);
        $postId2 = $this->db->lastInsertId();

        $this->db->table('videos')->insert(['title' => 'First Video']);
        $videoId = $this->db->lastInsertId();

        // Create tag
        $this->db->table('tags')->insert(['name' => 'PHP', 'slug' => 'php']);
        $tagId = $this->db->lastInsertId();

        // Attach tag to posts and video
        $this->db->table('tag_morphs')->insert([
            ['tag_id' => $tagId, 'morph_id' => $postId1, 'morph_type' => 'posts'],
            ['tag_id' => $tagId, 'morph_id' => $postId2, 'morph_type' => 'posts'],
            ['tag_id' => $tagId, 'morph_id' => $videoId, 'morph_type' => 'videos'],
        ]);

        // Tag should only return posts when calling posts()
        $tag = $this->db->model(TagModel::class)->find($tagId);
        $posts = $tag->posts;
        $this->assertCount(2, $posts);
        foreach ($posts as $post) {
            $this->assertInstanceOf(PostModel::class, $post);
        }

        // Tag should only return videos when calling videos()
        $videos = $tag->videos;
        $this->assertCount(1, $videos);
        $this->assertInstanceOf(VideoModel::class, $videos[0]);
    }

    public function testWithCountOnMorphToManyRelation()
    {
        $this->db->table('posts')->insert(['title' => 'Post A']);
        $postId1 = $this->db->lastInsertId();
        $this->db->table('posts')->insert(['title' => 'Post B']);
        $postId2 = $this->db->lastInsertId();

        $this->db->table('tags')->insert(['name' => 'PHP', 'slug' => 'php']);
        $tagId1 = $this->db->lastInsertId();
        $this->db->table('tags')->insert(['name' => 'Laravel', 'slug' => 'laravel']);
        $tagId2 = $this->db->lastInsertId();

        $this->db->table('tag_morphs')->insert([
            ['tag_id' => $tagId1, 'morph_id' => $postId1, 'morph_type' => 'posts'],
            ['tag_id' => $tagId2, 'morph_id' => $postId1, 'morph_type' => 'posts'],
            ['tag_id' => $tagId1, 'morph_id' => $postId2, 'morph_type' => 'posts'],
        ]);

        $posts = PostModel::query()->withCount('tags')->all();

        foreach ($posts as $post) {
            if ($post->title === 'Post A') {
                $this->assertEquals(2, $post->tags_count);
            } elseif ($post->title === 'Post B') {
                $this->assertEquals(1, $post->tags_count);
            }
        }
    }

    public function testWithCountDefaultsToZeroOnMorphToManyWhenNoRelated()
    {
        $this->db->table('posts')->insert(['title' => 'Untagged']);

        $posts = PostModel::query()->withCount('tags')->all();

        $this->assertEquals(0, $posts[0]->tags_count);
    }

    public function testWithCountAndOrderByOnMorphToManyRelation()
    {
        $this->db->table('posts')->insert(['title' => 'Few Tags']);
        $postId1 = $this->db->lastInsertId();
        $this->db->table('posts')->insert(['title' => 'Many Tags']);
        $postId2 = $this->db->lastInsertId();

        $this->db->table('tags')->insert(['name' => 'PHP', 'slug' => 'php']);
        $tagId1 = $this->db->lastInsertId();
        $this->db->table('tags')->insert(['name' => 'Laravel', 'slug' => 'laravel']);
        $tagId2 = $this->db->lastInsertId();
        $this->db->table('tags')->insert(['name' => 'ORM', 'slug' => 'orm']);
        $tagId3 = $this->db->lastInsertId();

        $this->db->table('tag_morphs')->insert([
            ['tag_id' => $tagId1, 'morph_id' => $postId1, 'morph_type' => 'posts'],
            ['tag_id' => $tagId1, 'morph_id' => $postId2, 'morph_type' => 'posts'],
            ['tag_id' => $tagId2, 'morph_id' => $postId2, 'morph_type' => 'posts'],
            ['tag_id' => $tagId3, 'morph_id' => $postId2, 'morph_type' => 'posts'],
        ]);

        $posts = PostModel::query()
            ->withCount('tags')
            ->orderBy('tags_count', 'desc')
            ->all();

        $this->assertEquals('Many Tags', $posts[0]->title);
        $this->assertEquals(3, $posts[0]->tags_count);
        $this->assertEquals('Few Tags', $posts[1]->title);
        $this->assertEquals(1, $posts[1]->tags_count);
    }

    public function testWithCountOnMorphedByManyRelation()
    {
        $this->db->table('posts')->insert(['title' => 'Post A']);
        $postId1 = $this->db->lastInsertId();
        $this->db->table('posts')->insert(['title' => 'Post B']);
        $postId2 = $this->db->lastInsertId();

        $this->db->table('videos')->insert(['title' => 'Video A']);
        $videoId = $this->db->lastInsertId();

        $this->db->table('tags')->insert(['name' => 'PHP', 'slug' => 'php']);
        $tagId1 = $this->db->lastInsertId();
        $this->db->table('tags')->insert(['name' => 'Laravel', 'slug' => 'laravel']);
        $tagId2 = $this->db->lastInsertId();

        $this->db->table('tag_morphs')->insert([
            ['tag_id' => $tagId1, 'morph_id' => $postId1, 'morph_type' => 'posts'],
            ['tag_id' => $tagId1, 'morph_id' => $postId2, 'morph_type' => 'posts'],
            ['tag_id' => $tagId1, 'morph_id' => $videoId, 'morph_type' => 'videos'],
            ['tag_id' => $tagId2, 'morph_id' => $postId1, 'morph_type' => 'posts'],
        ]);

        $tags = TagModel::query()->withCount('posts', 'videos')->all();

        foreach ($tags as $tag) {
            if ($tag->name === 'PHP') {
                $this->assertEquals(2, $tag->posts_count);
                $this->assertEquals(1, $tag->videos_count);
            } elseif ($tag->name === 'Laravel') {
                $this->assertEquals(1, $tag->posts_count);
                $this->assertEquals(0, $tag->videos_count);
            }
        }
    }

    public function testWithCountDefaultsToZeroOnMorphedByManyWhenNoRelated()
    {
        $this->db->table('tags')->insert(['name' => 'Unused', 'slug' => 'unused']);

        $tags = TagModel::query()->withCount('posts', 'videos')->all();

        $this->assertEquals(0, $tags[0]->posts_count);
        $this->assertEquals(0, $tags[0]->videos_count);
    }

    public function testWithCountAndOrderByOnMorphedByManyUsesCorrelatedSubquery()
    {
        $this->db->table('posts')->insert(['title' => 'Post A']);
        $postId1 = $this->db->lastInsertId();
        $this->db->table('posts')->insert(['title' => 'Post B']);
        $postId2 = $this->db->lastInsertId();
        $this->db->table('posts')->insert(['title' => 'Post C']);
        $postId3 = $this->db->lastInsertId();

        $this->db->table('tags')->insert(['name' => 'Popular', 'slug' => 'popular']);
        $tagIdA = $this->db->lastInsertId();
        $this->db->table('tags')->insert(['name' => 'Niche', 'slug' => 'niche']);
        $tagIdB = $this->db->lastInsertId();

        $this->db->table('tag_morphs')->insert([
            ['tag_id' => $tagIdA, 'morph_id' => $postId1, 'morph_type' => 'posts'],
            ['tag_id' => $tagIdA, 'morph_id' => $postId2, 'morph_type' => 'posts'],
            ['tag_id' => $tagIdA, 'morph_id' => $postId3, 'morph_type' => 'posts'],
            ['tag_id' => $tagIdB, 'morph_id' => $postId1, 'morph_type' => 'posts'],
        ]);

        $tags = TagModel::query()
            ->withCount('posts')
            ->orderBy('posts_count', 'desc')
            ->all();

        $this->assertEquals('Popular', $tags[0]->name);
        $this->assertEquals(3, $tags[0]->posts_count);
        $this->assertEquals('Niche', $tags[1]->name);
        $this->assertEquals(1, $tags[1]->posts_count);
    }
}
