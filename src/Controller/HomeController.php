<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpClient\HttpClient;


class HomeController extends AbstractController
{
    #[Route('/home', name: 'home')]
    public function home(): Response
    {
        $httpClient = HttpClient::create();
        //$response = $httpClient->request('GET', "http://ip-api.com/json/{$_SERVER['REMOTE_ADDR']}");
        $response = $httpClient->request('GET', "http://ip-api.com/json/195.101.237.253");
        //$response = $httpClient->request('GET', "http://ip-api.com/json/24.48.0.1");
        $statusCode = $response->getStatusCode();
        $contentType = $response->getHeaders()['content-type'][0];
        $content = $response->getContent();
        if(json_decode($content,true)['country']!== "France"){
            return $this->render('home/ipError.html.twig');
        }else{
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
        }
    }

    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->redirectToRoute('home');
    }
}
