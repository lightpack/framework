<?php

use Lightpack\Database\Lucid\Transformer;

class ProjectTransformer extends Transformer
{
    protected function data(Project $project): array
    {
        return [
            'id' => $project->id,
            'name' => $project->name,
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
