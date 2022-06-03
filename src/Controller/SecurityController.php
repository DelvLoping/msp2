<?php

namespace App\Controller;

use Symfony\Component\Mime\Email;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Email\EmailTwoFactorProvider;
use App\Service\IpService;


class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();


        return $this->render('security/login.html.twig', ['controller_name' => 'Login', 'last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/ipNotAllow', name: 'ip_not_allow')]
    public function registerBrowser(): Response
    {        
        return $this->render('security/ipError.html.twig');
    }

    #[Route('/home/ip', name: 'home_ip')]
    public function homeIp(ManagerRegistry $doctrine, MailerInterface $mailer): Response
    {
        $user=$this->getUser();
        $entityManager = $doctrine->getManager();

        // Requete vers api ip
        $ipService = new IpService();
        $ipService->setUrl("http://ip-api.com/json/");
        $ipService->queryIp("195.101.237.253");


        //Revoke access control
        $user->revokeRoles("ROLE_LOCAL_USER");
        $entityManager->flush();
        $token = new UsernamePasswordToken($this->getUser(), 'main', $this->getUser()->getRoles());
        $this->container->get('security.token_storage')->setToken($token);
        
        if($ipService->ipIsValid())
        {
            if($ipService->getCountryIp()=== "France"){
                
                if(!in_array($_SERVER['REMOTE_ADDR'],$user->getIp()))
                {
                    $user->setIp(array($_SERVER['REMOTE_ADDR']));
                    $message="We detect a new ip connection: ".$_SERVER['REMOTE_ADDR'];
                    $entityManager->flush();
                    $email = (new Email())
                        ->from('no-reply@test.com')
                        ->to($user->getEmail())
                        ->subject('Service Chatelet New Ip connection')
                        ->text($message);
        
                    $mailer->send($email);
                }
            }
            $user->setRoles(array("ROLE_LOCAL_USER"));
            $entityManager->flush();
            $token = new UsernamePasswordToken($this->getUser(), 'main', $this->getUser()->getRoles());
            $this->container->get('security.token_storage')->setToken($token);
            return $this->redirectToRoute('home_Browser');
        }
        return $this->redirectToRoute('ip_not_allow');
    
    }


    #[Route('/home/browser', name: 'home_Browser')]
    public function homeBrowser(ManagerRegistry $doctrine,MailerInterface $mailer): Response
    {
        
        $user=$this->getUser();
        $entityManager = $doctrine->getManager();
       
        $user->revokeRoles("ROLE_LOCAL_BROWSER_USER");
        $entityManager->flush();
        $token = new UsernamePasswordToken($this->getUser(), 'main', $this->getUser()->getRoles());
        $this->container->get('security.token_storage')->setToken($token);
        
        if(in_array(get_browser($_SERVER['HTTP_USER_AGENT'],true)['parent'],$user->getBrowser())){
            $user->setRoles(array("ROLE_LOCAL_BROWSER_USER"));
            $entityManager->flush();
            $token = new UsernamePasswordToken($this->getUser(), 'main', $this->getUser()->getRoles());
            $this->container->get('security.token_storage')->setToken($token);
            return $this->redirectToRoute('home');
        }else{
            function random_string(){
                $chars = '0123456789';
                $string = '';
                for($i=0; $i<6; $i++){
                    $string .= $chars[rand(0, strlen($chars)-1)];
                }
                return $string;
            }
            $code=random_string();
            $user->setEmailAuthCode($code);
            $entityManager->flush();
            $email = (new Email())
                ->from('no-reply@test.com')
                ->to($user->getEmail())
                ->subject('Service Chatelet Register Browser')
                ->text($code);
    
            $mailer->send($email);

            return $this->redirectToRoute('home_browser_register');
        }
        


    }

    #[Route('/home/browserRegister', name: 'home_browser_register')]
    public function homeBrowserRegister(ManagerRegistry $doctrine, Request $request): Response
    {
        $user=$this->getUser();
        $entityManager = $doctrine->getManager();

        $error=null;

        if ($request->getMethod() == 'POST') {
            if($request->request->get('authCodeParameterName', '')===$user->getEmailAuthCode())
            {
                $browser=$user->getBrowser();
                array_push($browser,get_browser($_SERVER['HTTP_USER_AGENT'],true)['parent']);
                $user->setBrowser($browser);
                $user->setRoles(array("ROLE_LOCAL_BROWSER_USER"));
                $entityManager->flush();
                $token = new UsernamePasswordToken($this->getUser(), 'main', $this->getUser()->getRoles());
                $this->container->get('security.token_storage')->setToken($token);
                return $this->redirectToRoute('home');
            }else{
                $error="Invalid Code";
            }
        }
        return $this->render('security/browserRegister.html.twig', ['controller_name' => 'Register Browser',
            'browser' => get_browser($_SERVER['HTTP_USER_AGENT'],true)['parent'],
            'error'=> $error
        ]);

    }

}
