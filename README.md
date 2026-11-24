# php-email-library

## Wrapper for PHPmailer for ease of use

### Usage

```php

use Email\Email;
use Email\EmailAddress as Address;

$data = [
    "from" => Address::newAddress($email, $name),
    "to" => [
        Address::newAddress("info@example.com", "Max Mustermann")
    ],
    // add custom fields to message body
    // (keys and values are HTML-escaped, line breaks are kept)
    "body" => [
        "subject" => $subject,
        "customField" => $customField
    ],
    // attach uploaded files to mail – pass entries of $_FILES.
    // Only real HTTP uploads are accepted (is_uploaded_file), see "allowLocalAttachments".
    "files" => [
      $_FILES["file"]
    ]
];

$settings = [
    // enables recaptcha authentication
    "recaptcha" => [
        "secret" => getenv("RECAPTCHA_SECRET"), // keep secrets out of the code/repository
        "response" => $recaptchaResponse,
        "scoreThreshold" => 0.9, // recaptcha v3 score threshold (default = 0.5)
        // optional, recommended:
        "action" => "php_email_form_submit",       // must match grecaptcha.execute(..., { action })
        "hostname" => ["www.example.com", "example.com"],
        "remoteIp" => $_SERVER["REMOTE_ADDR"] ?? null
    ],
    "html" => true, // enable html mail
    // only needed to attach files that are NOT direct HTTP uploads (e.g. generated PDFs)
    // "allowLocalAttachments" => true
];

try {
    $mail = new Email($data, $settings);

    if ($mail->send()) {
        echo "OK";
    }
    // on failure send() returns false; details are written to the server error log only
} catch (Exception\AuthError $e) {
    // recaptcha failed (HTTP status 401 is set)
    echo "Authentifizierung fehlgeschlagen.";
} catch (Throwable $e) {
    // never echo $e to visitors – it contains file paths and stack traces
    error_log($e);
    echo "Ihre Nachricht konnte nicht gesendet werden.";
}

```

### Security notes

- Field names and values in `body` are HTML-escaped before they are written to the HTML mail.
- Header values (subject, names) are stripped of line breaks and control characters.
- Attachments must be real HTTP uploads unless `allowLocalAttachments` is set.
- `send()` never prints SMTP/PHPMailer error details to the visitor.
- reCAPTCHA verification fails closed if Google is unreachable or the response is invalid.
