<?php

require_once 'Comment.php';

use \Lightpack\Database\Lucid\Model;

class Task extends Model
{   
    protected $table = 'tasks';

    public function comments()
    {
        return $this->hasMany(Comment::class, 'task_id');
    }
}