<?php

namespace Exception;

use Exception;

class AuthError extends Exception
{
  public function __construct()
  {
    http_response_code(401);
  }
}
