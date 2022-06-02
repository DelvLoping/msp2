<?php

namespace App\Controller;

use Symfony\Component\Mime\Email;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Email\EmailTwoFactorProvider;



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


        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
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

        //Requete vers api ip
        $httpClient = HttpClient::create();
        //$response = $httpClient->request('GET', "http://ip-api.com/json/{$_SERVER['REMOTE_ADDR']}");
        $response = $httpClient->request('GET', "http://ip-api.com/json/195.101.237.253");
        //$response = $httpClient->request('GET', "http://ip-api.com/json/24.48.0.1");
        $statusCode = $response->getStatusCode();
        $contentType = $response->getHeaders()['content-type'][0];
        $content = $response->getContent();
        $roles=$user->getRoles();
        //Revoke access control
        if(in_array("ROLE_LOCAL_USER",$roles)){
            $user->setRoles(array("ROLE_USER"));
            $entityManager->flush();
            $token = new UsernamePasswordToken($this->getUser(), 'main', $this->getUser()->getRoles());
            $this->container->get('security.token_storage')->setToken($token);
        }

        if(json_decode($content,true)['country']=== "France")
        {
            if(in_array($_SERVER['REMOTE_ADDR'],$user->getIp())){
                $user->setRoles(array("ROLE_LOCAL_USER"));
                $entityManager->flush();
                $token = new UsernamePasswordToken($this->getUser(), 'main', $this->getUser()->getRoles());
                $this->container->get('security.token_storage')->setToken($token);
                return $this->redirectToRoute('home_Browser');
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
                    ->subject('Service Chatelet Register Ip')
                    ->text($code);
        
                $mailer->send($email);
        
                return $this->redirectToRoute('home_ip_register');
            }
        }else{
            return $this->redirectToRoute('ip_not_allow');
        }
    
    }

    #[Route('/home/ipRegister', name: 'home_ip_register')]
    public function homeIpRegister(ManagerRegistry $doctrine, Request $request): Response
    {
        $user=$this->getUser();
        $entityManager = $doctrine->getManager();

        $error=null;

        if ($request->getMethod() == 'POST') {
            if($request->request->get('authCodeParameterName', '')===$user->getEmailAuthCode())
            {
                $ip=$user->getIp();
                array_push($ip,$_SERVER['REMOTE_ADDR']);
                $user->setIp($ip);

                $user->setRoles(array("ROLE_LOCAL_USER"));
                $entityManager->flush();
                $token = new UsernamePasswordToken($this->getUser(), 'main', $this->getUser()->getRoles());
                $this->container->get('security.token_storage')->setToken($token);
                return $this->redirectToRoute('home_Browser');
            }else{
                $error="Invalid Code";
            }
        }
        return $this->render('security/ipRegister.html.twig', [
            'ip' => $_SERVER['REMOTE_ADDR'],
            'error'=> $error
        ]);
    }


    #[Route('/home/browser', name: 'home_Browser')]
    public function homeBrowser(ManagerRegistry $doctrine,MailerInterface $mailer): Response
    {
        
        $user=$this->getUser();
        $entityManager = $doctrine->getManager();
       
        $roles=$user->getRoles();
        //Revoke access control
        if(in_array("ROLE_LOCAL_BROWSER_USER",$roles)){
            $user->setRoles(array("ROLE_LOCAL_USER"));
            $entityManager->flush();
            $token = new UsernamePasswordToken($this->getUser(), 'main', $this->getUser()->getRoles());
            $this->container->get('security.token_storage')->setToken($token);
        }
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
        return $this->render('security/browserRegister.html.twig', [
            'browser' => get_browser($_SERVER['HTTP_USER_AGENT'],true)['parent'],
            'error'=> $error
        ]);

    }

}
