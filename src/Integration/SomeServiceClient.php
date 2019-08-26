<?php

namespace src\Integration;

class SomeServiceClient
{
    protected $host;
    protected $user;
    protected $password;

    public function __construct($host, $user, $password)
    {
        $this->host     = $host;
        $this->user     = $user;
        $this->password = $password;
    }


    public function get(array $request): array
    {
        // returns a response from external service
    }
}
