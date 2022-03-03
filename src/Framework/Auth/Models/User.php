<?php

namespace Lightpack\Auth\Models;

use Lightpack\Database\Lucid\Model;

class User extends Model
{
    /** @inheritDoc */
    protected $table = 'users';

    /** @inheritDoc */
    protected $primaryKey = 'id';

    /** @inheritDoc */
    protected $timestamps = true;
}