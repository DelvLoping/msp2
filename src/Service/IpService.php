<?php

namespace App\Service;

use App\Service\IIpService;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Response\CurlResponse;

class IpService implements IIpService{
    private HttpClient $httpClient;
    private string $url;
    private CurlResponse $response;

    function __construct(){
        $this->httpClient = new HttpClient();
    }

    public function queryIp(string $ip):void
    {
        $response=$this->httpClient::create()->request('GET',$this->getUrl()."".$ip);
        //$statusCode = $response->getStatusCode();
        //$contentType = $response->getHeaders()['content-type'][0];
        $this->setResponse($response);
    }

    public function getResponse():CurlResponse
    {
        return $this->response;
    }

    private function setResponse(CurlResponse $response):void
    {
        $this->response=$response;
    }

    public function getUrl():string
    {
        return $this->url;
    }

    public function setUrl(string $url):void
    {
        $this->url=$url;
    }

    public function ipIsValid():bool
    {
        $content=json_decode($this->getResponse()->getContent(),true);
        if($content['status']==='success'){
            return true;
        }
        return false;
    }

    public function getCountryIp():string
    {
        return json_decode($this->getResponse()->getContent(),true)['country'];
    }
}