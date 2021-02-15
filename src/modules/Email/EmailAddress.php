<?php

namespace Email;

class EmailAddress
{
    public function __construct()
    {
    }

    public static function newAddress(string $address, string $name)
    {
        return [
            "address" => $address,
            "name" => $name
        ];
    }
}
