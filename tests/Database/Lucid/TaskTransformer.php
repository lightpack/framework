<?php

use Lightpack\Database\Transformer;

class TaskTransformer extends Transformer
{
    protected function data($model): array 
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
        ];
    }

    protected function relations(): array 
    {
        return [
            'comments' => CommentTransformer::class,
            'project' => ProjectTransformer::class,
        ];
    }
}