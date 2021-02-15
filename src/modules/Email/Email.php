<?php

namespace Email;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Exception\MissingDataException;
use Exception\MissingMailBodyException;
use Exception\MissingMailFromException;
use Exception\MissingMailToException;

class Email
{
    private $phpmailer;

    public function __construct(array $data, array $options = null)
    {
        $this->phpmailer = new PHPMailer(true);

        if (!$data) {
            throw new MissingDataException("No data was supplied to class constructor!");
        }

        $this->setEmailProperties($data);

        if ($options) {
            $this->options = $options;
        }
    }

    public function send()
    {
        try {
            $this->phpmailer->send();
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$this->phpmailer->ErrorInfo}";
        }
    }

    public function setOptions($options)
    {
        foreach ($options as $option => $value) {
            switch ($option) {
                case "smtp":
                    $this->setSMTPSettings($value["settings"]);
                    break;

                case "html":
                    $this->phpmailer->isHTML($value);
                    break;
            }
        }
    }

    private function setEmailProperties(array $data)
    {
        if ($data["to"]) {
            $this->setRecipients($data["to"]);
        } else {
            throw new MissingMailToException("No recipient data was supplied to class constructor!\nMissing key: 'to'");
        }

        if ($data["from"]) {
            $this->setSender($data["from"]);
        } else {
            throw new MissingMailFromException("No sender data was supplied to class constructor!\nMissing key: 'from'");
        }

        if ($data["cc"]) {
            $this->setCC($data["cc"]);
        }

        if ($data["bcc"]) {
            $this->setBCC($data["bcc"]);
        }

        if ($data["body"]) {
            $this->setBody($data["body"]);
        } else {
            throw new MissingMailBodyException("No body data was supplied to class constructor!\nMissing key: 'body'");
        }
    }

    private function setSender($from)
    {
        $this->phpmailer->setFrom(
            $from["address"],
            $from["name"]
        );
    }

    private function setRecipients($to)
    {
        foreach ($to as $recipient) {
            $this->phpmailer->addAddress(
                $recipient["address"],
                $recipient["name"]
            );
        }
    }

    private function setCC($cc)
    {
        $this->phpmailer->addCC($cc);
    }

    private function setBCC($bcc)
    {
        $this->phpmailer->addBCC($bcc);
    }

    private function setBody($body)
    {
        foreach ($body["body"] as $key => $value) {
            if ($key !== "subject") {
                $this->phpmailer->body .= $value . "<br>";
            } else {
                $this->phpmailer->subject = $value;
            }
        }
    }

    private function setSMTPSettings($settings)
    {
        if ($settings["isSmtp"]) {
            $this->phpmailer->isSMTP();
            $this->phpmailer->Host = $settings["settings"]["host"];
            $this->phpmailer->SMTPAuth = $settings["settings"]["auth"];
            $this->phpmailer->Username = $settings["settings"]["username"];
            $this->phpmailer->Password = $settings["settings"]["password"];
            $this->phpmailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->phpmailer->Port = $settings["settings"]["port"];
        } else {
            $this->phpmailer->isMail();
        }
    }
}
