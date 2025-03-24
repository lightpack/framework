<?php

use Lightpack\Database\Transformer;

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