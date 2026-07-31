<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;
use OpenApi\Annotations as OA;

abstract class Controller extends BaseController
{
    use AuthorizesRequests;
}
