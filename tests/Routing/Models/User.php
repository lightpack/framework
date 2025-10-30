<?php

use Lightpack\Database\Lucid\Model;

class User extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
}
