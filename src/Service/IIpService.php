<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;

interface IIpService {

    public function queryIp(string $ip);

    public function setUrl(string $url);

    public function ipIsValid();

    public function getCountryIp();
}