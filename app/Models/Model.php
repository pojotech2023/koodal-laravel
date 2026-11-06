<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;

/**
 * Shared base. Exists so cross-cutting concerns (a global date format, a
 * default per-page, future auditing hooks) have one place to live rather than
 * a trait applied to thirty classes.
 */
abstract class Model extends EloquentModel
{
    protected $guarded = [];
}
