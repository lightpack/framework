<?php

require_once 'Task.php';

use \Lightpack\Database\Lucid\Model;

class Project extends Model
{   
    protected $table = 'projects';

    public function tasks()
    {
        return $this->hasMany(Tasks::class, 'project_id');
    }

    public function comments()
    {
        return $this->hasManyThrough(Comment::class, Task::class, 'project_id', 'task_id');
    }
}