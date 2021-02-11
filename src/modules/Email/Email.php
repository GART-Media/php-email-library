<?php

namespace Email;

use PHPMailer\PHPMailer\PHPMailer;
use Exception\MissingDataException;

class Email
{
    private $phpmailer;
    private $to;
    private $from;
    private $cc;
    private $bcc;
    private $body;

    public function __construct(array $data, array $options = null)
    {
        $this->phpmailer = new PHPMailer(true);

        if (!$data) {
            throw new MissingDataException("No data was supplied to class constructor!");
        }

        $this->setEmailProperties($data);
    }

    private function setEmailProperties(array $data)
    {
        if ($data["to"]) {
            $this->to = $data["to"];
        }

        if ($data["from"]) {
            $this->from = $data["from"];
        }

        if ($data["cc"]) {
            $this->cc = $data["cc"];
        }

        if ($data["bcc"]) {
            $this->bcc = $data["bcc"];
        }

        if ($data["body"]) {
            $this->body = [];
            foreach ($data["body"] as $key => $value) {
                $this->body[$key] = $value;
            }
        }
    }
}
