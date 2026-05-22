<?php

use Lightpack\Database\Lucid\Transformer;

class TaskTransformer extends Transformer
{
    protected function data(Task $task): array
    {
        return [
            'id' => $task->id,
            'name' => $task->name,
        ];
    }

    protected function transformerMap(): array
    {
        return [
            'comments' => CommentTransformer::class,
            'project' => ProjectTransformer::class,
        ];
    }
}
