<?php

namespace Exception;

use Exception;

/**
 * Wird geworfen, wenn ein Dateianhang kein gültiger HTTP-Upload ist
 * (siehe Email::setAttachments und Option "allowLocalAttachments").
 */
class InvalidAttachmentException extends Exception
{
}
