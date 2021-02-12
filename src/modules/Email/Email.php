<?php

namespace Email;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Exception\MissingDataException;
use Exception\MissingMailBodyException;

class Email
{
    private $phpmailer;
    private $body;

    public function __construct(array $data, array $options = null)
    {
        $this->phpmailer = new PHPMailer(true);

        if (!$data) {
            throw new MissingDataException("No data was supplied to class constructor!");
        }

        $this->setEmailProperties($data);
    }

    public function send()
    {
        try {
            $this->phpmailer->send();
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$this->phpmailer->ErrorInfo}";
        }
    }

    private function setEmailProperties(array $data)
    {
        $this->setRecipients($data["to"]);
        $this->setSender($data["from"]);
        $this->setCC($data["cc"]);
        $this->setBCC($data["bcc"]);

        //TODO: split body props 
        if ($data["body"]["subject"]) {
            foreach ($data["body"] as $key => $value) {
                $this->body[$key] = $value;
            }
        } else {
            throw new MissingMailBodyException("No mail body supplied to class constructor.");
        }
    }

    private function setSender($from)
    {
        if ($from) {
            $this->phpmailer->setFrom(
                $from["address"],
                $from["name"]
            );
        }
    }

    private function setRecipients($to)
    {
        if ($to) {
            foreach ($to as $recipient) {
                $this->phpmailer->addAddress(
                    $recipient["address"],
                    $recipient["name"]
                );
            }
        }
    }

    private function setCC($cc)
    {
        if ($cc) {
            $this->phpmailer->addCC($cc);
        }
    }

    private function setBCC($bcc)
    {
        if ($bcc) {
            $this->phpmailer->addBCC($bcc);
        }
    }
}
