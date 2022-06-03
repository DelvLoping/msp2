<?php

namespace App\Security;

use App\Entity\User;
use App\Security\LdapService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Ldap\Entry;

class LoginHelper
{
    private $em;
    private $ldapService;
    private $entry;

    function __construct(EntityManagerInterface $entiyManager, LdapService $ldapService)
    {
        $this->em = $entiyManager;
        $this->ldapService = $ldapService;
    }

    public function checkUserLogin($username, $password) : bool
    {
        $entry = $this->ldapService->getEntry($username);

        if ($entry instanceof Entry) {

            $dn = $entry->getDn();

            try {
                $this->ldapService->bind($dn, $password);

                $this->entry = $entry;
                return true;
            } catch (\Exception $exception) {
                return false;
            }
        }

        return false;
    }

    public function getUserByUsername($username)
    {
        $userRepo = $this->em->getRepository(User::class);
        $currentUser = $userRepo->findUserByUsername($username);

        if(count($currentUser)===0)
        {
           
            $attributes = array_map(function ($x) { return $x[0]; }, $this->entry->getAttributes());
            $user = new User();
            $user->setUsername($username)
                ->setEmail($attributes['mail'])
                ->setRoles(["ROLE_USER"]);
            $this->em->persist($user);

            $currentUser = $user;
        }else{
            $currentUser=$currentUser[0];
        }
        // TODO Update user (name, displayname, etc.)

        $this->em->flush();

        return $currentUser;
    }
}
