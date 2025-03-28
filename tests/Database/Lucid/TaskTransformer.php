<?php

use Lightpack\Database\Lucid\Transformer;

class TaskTransformer extends Transformer
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
            'comments' => CommentTransformer::class,
            'project' => ProjectTransformer::class,
        ];
    }
}