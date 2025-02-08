<?php

require_once 'User.php';

use \Lightpack\Database\Lucid\Model;

class Role extends Model
{   
    protected $table = 'roles';

    public function users()
    {
        return $this->pivot(User::class, 'user_role', 'user_id', 'role_id');
    }
}