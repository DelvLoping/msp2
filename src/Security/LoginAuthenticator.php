<?php

namespace App\Security;

use App\Security\LoginHelper;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class LoginAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    private UrlGeneratorInterface $urlGenerator;
    private ContainerInterface $container;

    public function __construct(UrlGeneratorInterface $urlGenerator, ContainerInterface $container)
    {
        $this->urlGenerator = $urlGenerator;
        $this->container = $container;
    }

    public function authenticate(Request $request): Passport
    {
        $username = $request->request->get('username', '');
        $password = $request->request->get('password', '');
        
        $request->getSession()->set(Security::LAST_USERNAME, $username);
        $loginHelper = $this->container->get(LoginHelper::class);

        $isValidUser = $loginHelper->checkUserLogin($username, $password);

        if($isValidUser)
        {
            $user = $loginHelper->getUserByUsername($username);

            return new SelfValidatingPassport(new UserBadge($username, function () use($user) { return $user; }));
        }
        else
        {

            return new Passport(
                new UserBadge($username),
                new PasswordCredentials($password),
                [
                    new CsrfTokenBadge('authenticate', $request->get('_csrf_token'))
                ]
            );
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {

        return new RedirectResponse($this->urlGenerator->generate('home_ip'));
        

        // For example:
        // return new RedirectResponse($this->urlGenerator->generate('some_route'));
        throw new \Exception('TODO: provide a valid redirect inside '.__FILE__);
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
