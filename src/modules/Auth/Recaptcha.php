<?php

namespace Auth;

class Recaptcha
{
    public static $url = 'https://www.google.com/recaptcha/api/siteverify';

    /** Maximale Wartezeit auf Google in Sekunden, damit das Formular nicht hängt. */
    public static $timeout = 10;

    /**
     * Prüft ein reCAPTCHA-Token bei Google.
     *
     * Gibt das dekodierte Ergebnis als Objekt zurück oder null, wenn kein Token
     * übergeben wurde oder Google nicht erreichbar war bzw. ungültig antwortet.
     * null wird von Email::send() als fehlgeschlagene Prüfung behandelt.
     */
    public static function verify($captchaResponse, $secret, $remoteIp = null)
    {
        if (!is_string($captchaResponse) || $captchaResponse === '' || strlen($captchaResponse) > 4096) {
            return null;
        }

        $context = self::createStreamContext($captchaResponse, (string) $secret, $remoteIp);
        return self::getCaptchaResponse($context);
    }

    private static function createStreamContext($captchaResponse, $secret, $remoteIp)
    {
        $data = array(
            'secret' => $secret,
            'response' => $captchaResponse
        );

        if (is_string($remoteIp) && filter_var($remoteIp, FILTER_VALIDATE_IP)) {
            $data['remoteip'] = $remoteIp;
        }

        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($data),
                'timeout' => self::$timeout
            )
        );

        return stream_context_create($options);
    }

    private static function getCaptchaResponse($context)
    {
        $captchaResponse = @file_get_contents(self::$url, false, $context);

        if ($captchaResponse === false) {
            error_log("php-email-library: reCAPTCHA verification request failed.");
            return null;
        }

        $decodedCaptchaResponse = json_decode($captchaResponse);

        return is_object($decodedCaptchaResponse) ? $decodedCaptchaResponse : null;
    }
}
