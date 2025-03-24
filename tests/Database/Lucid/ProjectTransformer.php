<?php

use Lightpack\Database\Transformer;

class ProjectTransformer extends Transformer 
{
    protected function data($model): array 
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
        ];
    }

    protected function transformerMap(): array 
    {
        return [
            'tasks' => TaskTransformer::class,
            'comments' => CommentTransformer::class,
        ];
    }
}