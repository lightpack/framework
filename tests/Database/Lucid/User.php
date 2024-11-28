<?php

require_once 'Role.php';

use \Lightpack\Database\Lucid\Model;
use Lightpack\Database\Lucid\Pivot;

class User extends Model
{   
    protected $table = 'users';

    public function roles(): Pivot
    {
        return $this->pivot(Role::class, 'role_user', 'user_id', 'role_id');
    }
}