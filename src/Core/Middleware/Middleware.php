<?php

namespace App\Core\Middleware;

use App\Core\Request;
use App\Core\Response;

/**
 * Middleware bazasi. handle() null qaytarsa quvur davom etadi;
 * Response qaytarsa so'rov shu yerda to'xtaydi (masalan redirect / 403).
 */
interface Middleware
{
    public function handle(Request $request): ?Response;
}
