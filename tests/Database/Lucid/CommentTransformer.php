<?php

use Lightpack\Database\Lucid\Transformer;

class CommentTransformer extends Transformer
{
    protected function data(Comment $comment): array
    {
        return [
            'id' => $comment->id,
            'content' => $comment->content,
        ];
    }
}
