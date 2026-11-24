<?php

namespace Email;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Auth\Recaptcha;
use Exception\AuthError;
use Exception\InvalidAttachmentException;
use Exception\MissingDataException;
use Exception\MissingMailBodyException;
use Exception\MissingMailFromException;
use Exception\MissingMailToException;

class Email
{
    private $phpmailer;
    private $auth;
    private $authEnabled = false;
    private $scoreThreshold = 0.5;
    private $expectedAction = null;
    private $expectedHostnames = null;
    private $allowLocalAttachments = false;

    public function __construct(array $data, ?array $options = null)
    {
        $this->phpmailer = new PHPMailer(true);

        if (!$data) {
            throw new MissingDataException("No data was supplied to class constructor!");
        }

        // Muss vor setEmailProperties() bekannt sein (Prüfung der Anhänge).
        if (!empty($options["allowLocalAttachments"])) {
            $this->allowLocalAttachments = true;
        }

        $this->setEmailProperties($data);

        if (isset($options)) {
            $this->setOptions($options);
        }
    }

    /**
     * Versendet die E-Mail.
     *
     * Gibt true bei Erfolg zurück, sonst false. Fehlerdetails (SMTP-Server,
     * PHPMailer-Meldungen) werden nur ins Server-Log geschrieben und nicht an
     * den Besucher ausgegeben.
     *
     * @throws AuthError wenn die reCAPTCHA-Prüfung fehlschlägt (HTTP 401)
     */
    public function send(): bool
    {
        if ($this->authEnabled) {
            $this->assertAuthenticated();
        }

        try {
            $this->phpmailer->send();
            return true;
        } catch (Exception $e) {
            error_log("php-email-library: Mail could not be sent. " . $this->phpmailer->ErrorInfo . " " . $e->getMessage());
            echo "Message could not be sent.";
            return false;
        }
    }

    public function setOptions($options)
    {
        foreach ($options as $option => $value) {
            switch ($option) {
                case "smtp":
                    $this->setSMTPSettings($value);
                    break;

                case "html":
                    $this->phpmailer->isHTML($value);
                    break;

                case "charset":
                    $this->phpmailer->CharSet = $value;
                    break;

                case "recaptcha":
                    if (isset($value["scoreThreshold"])) {
                        $this->scoreThreshold = $value["scoreThreshold"];
                    }

                    // Optional: erwartete Aktion (grecaptcha.execute(..., {action})) und
                    // Hostnamen prüfen, damit Tokens anderer Formulare/Seiten nicht gelten.
                    if (isset($value["action"])) {
                        $this->expectedAction = (string) $value["action"];
                    }

                    if (isset($value["hostname"])) {
                        $this->expectedHostnames = (array) $value["hostname"];
                    }

                    $this->auth = Recaptcha::verify(
                        $value["response"] ?? null,
                        $value["secret"] ?? "",
                        $value["remoteIp"] ?? null
                    );
                    $this->authEnabled = true;
                    break;
            }
        }
    }

    /**
     * Prüft das reCAPTCHA-Ergebnis. Schlägt sicher fehl, wenn Google nicht
     * erreichbar war, das Token ungültig ist oder Score/Aktion/Hostname nicht passen.
     */
    private function assertAuthenticated()
    {
        $result = $this->auth;

        if (!is_object($result) || empty($result->success)) {
            throw new AuthError("Authentifizierung fehlgeschlagen.");
        }

        if (!isset($result->score) || $result->score < $this->scoreThreshold) {
            throw new AuthError("Authentifizierung fehlgeschlagen. Score zu niedrig.");
        }

        if ($this->expectedAction !== null && (!isset($result->action) || $result->action !== $this->expectedAction)) {
            throw new AuthError("Authentifizierung fehlgeschlagen. Unerwartete Aktion.");
        }

        if ($this->expectedHostnames !== null && (!isset($result->hostname) || !in_array($result->hostname, $this->expectedHostnames, true))) {
            throw new AuthError("Authentifizierung fehlgeschlagen. Unerwarteter Hostname.");
        }
    }

    private function setEmailProperties(array $data)
    {
        if (!empty($data["to"])) {
            $this->setRecipients($data["to"]);
        } else {
            throw new MissingMailToException("No recipient data was supplied to class constructor!\nMissing key: 'to'");
        }

        if (!empty($data["from"])) {
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

        if (!empty($data["body"])) {
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
            filter_var(trim((string) ($from["address"] ?? "")), FILTER_SANITIZE_EMAIL),
            self::cleanHeaderValue($from["name"] ?? "")
        );
    }

    private function setRecipients($to)
    {
        foreach ($to as $recipient) {
            $this->phpmailer->addAddress(
                filter_var(trim((string) ($recipient["address"] ?? "")), FILTER_SANITIZE_EMAIL),
                self::cleanHeaderValue($recipient["name"] ?? "")
            );
        }
    }

    private function setCC($cc)
    {
        $this->phpmailer->addCC(filter_var(trim((string) $cc), FILTER_SANITIZE_EMAIL));
    }

    private function setBCC($bcc)
    {
        $this->phpmailer->addBCC(filter_var(trim((string) $bcc), FILTER_SANITIZE_EMAIL));
    }

    /**
     * Schreibt alle Felder als "Bezeichnung: Wert" in den HTML-Body.
     * Bezeichnung UND Wert werden HTML-escaped – beide können aus
     * Formulardaten stammen (z. B. wenn ein Formular alle POST-Felder übergibt).
     */
    private function setBody($body)
    {
        foreach ($body as $key => $value) {
            if ($key === "subject") {
                $this->phpmailer->Subject = self::cleanHeaderValue($value);
                continue;
            }

            $label = self::escape((string) $key);
            $text = nl2br(self::escape(self::stringify($value)), false);
            $this->phpmailer->Body .= "<b>$label</b>: $text <br>";
        }
    }

    /**
     * Hängt nur echte, per HTTP hochgeladene Dateien an (is_uploaded_file).
     * Sonst könnte ein Aufrufer, der Pfade aus Benutzereingaben übernimmt,
     * beliebige Dateien des Servers verschicken. Wer bewusst lokale Dateien
     * anhängen will, setzt die Option "allowLocalAttachments" => true.
     */
    private function setAttachments($attachments)
    {
        foreach ($attachments as $file) {
            if (!is_array($file) || !isset($file["tmp_name"], $file["name"]) || !is_string($file["tmp_name"])) {
                throw new InvalidAttachmentException("Ungültige Angaben zu einem Dateianhang.");
            }

            if (isset($file["error"]) && $file["error"] !== UPLOAD_ERR_OK) {
                throw new InvalidAttachmentException("Der Upload einer Datei ist fehlgeschlagen.");
            }

            if (!$this->allowLocalAttachments && !is_uploaded_file($file["tmp_name"])) {
                throw new InvalidAttachmentException("Der Dateianhang ist kein gültiger Upload.");
            }

            // Nur den reinen Dateinamen verwenden (kein Pfad, keine Steuerzeichen).
            $name = basename(str_replace("\\", "/", self::cleanHeaderValue($file["name"])));

            $this->phpmailer->addAttachment($file["tmp_name"], $name);
        }
    }

    private function setSMTPSettings($settings)
    {
        if (isset($settings["settings"])) {
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

    /** Wandelt beliebige Formularwerte (auch Arrays, z. B. Checkbox-Gruppen) in Text um. */
    private static function stringify($value): string
    {
        if (is_array($value)) {
            return implode(", ", array_map([self::class, "stringify"], $value));
        }

        if (is_bool($value)) {
            return $value ? "ja" : "nein";
        }

        if ($value === null) {
            return "";
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return (string) json_encode($value);
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, "UTF-8");
    }

    /** Entfernt Zeilenumbrüche und Steuerzeichen aus Werten für Mail-Header. */
    private static function cleanHeaderValue($value): string
    {
        $value = self::stringify($value);
        $cleaned = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value);

        if ($cleaned === null) {
            // Ungültiges UTF-8: ohne /u-Modifier bereinigen
            $cleaned = preg_replace('/[\x00-\x1F\x7F]+/', ' ', $value);
        }

        return trim((string) $cleaned);
    }
}
