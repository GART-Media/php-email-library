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
    "body" => [
        "subject" => $subject,
        "customField" => $customField
    ],
    // attach files to mail
    "files" => [
      ["tmp_name" => "temp", "name" => "test.txt"],
      ["tmp_name" => "temp2", "name" => "test2.txt"]
    ]
];

$settings = [
    // enables recaptcha authentication
    "recaptcha" => [
        "secret" => "your recaptcha secret",
        "response" => $recaptchaResponse
    ],
    "html" => true // enable html mail
];

$mail = new Email($data, $settings);

try {
    $mail->send();
} catch (Exception $e) {
    echo $e;
}

```
