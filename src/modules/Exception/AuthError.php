<?php

namespace Exception;

use Exception;

class AuthError extends Exception
{
  public function __construct($message = "Authentifizierung fehlgeschlagen.")
  {
    parent::__construct($message);
    http_response_code(401);
  }
}
