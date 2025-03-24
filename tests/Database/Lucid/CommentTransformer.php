<?php

use Lightpack\Database\Lucid\Transformer;

class CommentTransformer extends Transformer
{
    protected function data($model): array 
    {
        return [
            'id' => $model->id,
            'content' => $model->content,
        ];
    }
}