<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Email\EmailTwoFactorProvider;
use Scheb\TwoFactorBundle\Mailer\SymfonyAuthCodeMailer;
use Doctrine\Persistence\ManagerRegistry;

class HomeController extends AbstractController
{
    #[Route('/home/home', name: 'home')]
    public function home(ManagerRegistry $doctrine): Response
    {
        
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->redirectToRoute('home');
    }
}
