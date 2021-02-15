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

        if (isset($options)) {
            $this->setOptions($options);
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

                case "charset":
                    $this->phpmailer->CharSet = $value;
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

        if (isset($data["cc"])) {
            $this->setCC($data["cc"]);
        }

        if (isset($data["bcc"])) {
            $this->setBCC($data["bcc"]);
        }

        if ($data["body"]) {
            $this->setBody($data["body"]);
        } else {
            throw new MissingMailBodyException("No body data was supplied to class constructor!\nMissing key: 'body'");
        }

        if (isset($data["files"])) {
            $this->setAttachments($data["files"]);
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
        foreach ($body as $key => $value) {
            if ($key !== "subject") {
                $this->phpmailer->Body .= $value . "<br>";
            } else {
                $this->phpmailer->Subject = $value;
            }
        }
    }

    private function setAttachments($attachments)
    {
        foreach ($attachments as $file) {
            $this->phpmailer->addAttachment($file["tmp_name"], $file["name"]);
        }
    }

    private function setSMTPSettings($settings)
    {
        if ($settings["isSmtp"]) {
            $this->phpmailer->isSMTP();
            $this->phpmailer->Host = $settings["settings"]["host"];

            if ($settings["settings"]["auth"]) {
                $this->phpmailer->SMTPAuth = $settings["settings"]["auth"];
                $this->phpmailer->Username = $settings["settings"]["username"];
                $this->phpmailer->Password = $settings["settings"]["password"];
            }

            $this->phpmailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->phpmailer->Port = $settings["settings"]["port"];
        } else {
            $this->phpmailer->isMail();
        }
    }
}
