<?php

namespace Auth;

class Recaptcha
{
    public static $url = 'https://www.google.com/recaptcha/api/siteverify';

    public static function verify($captchaResponse, $secret)
    {
        $context = self::createStreamContext($captchaResponse, $secret);
        return self::getCaptchaResponse($context);
    }

    private static function createStreamContext($captchaResponse, $secret)
    {
        $data = array(
            'secret' => $secret,
            'response' => $captchaResponse
        );

        $options = array(
            'http' => array(
                'method' => 'POST',
                'content' => http_build_query($data)
            )
        );

        return stream_context_create($options);
    }

    private static function getCaptchaResponse($context)
    {
        $captchaResponse = @file_get_contents(self::$url, false, $context);
        $decodedCaptchaResponse = json_decode($captchaResponse);

        return $decodedCaptchaResponse->success;
    }
}
