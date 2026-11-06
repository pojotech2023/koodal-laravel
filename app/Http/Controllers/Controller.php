<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * Laravel 11 ships a bare base controller. AuthorizesRequests is added back
 * because `$this->authorize(...)` is the house idiom for permission checks and
 * policies throughout the admin surface.
 */
abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
