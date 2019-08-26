<?php

namespace src\Integration;

interface DataProvider
{
    public function get(array $request): array;
}