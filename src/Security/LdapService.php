<?php

namespace App\Security;

use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Ldap\Exception\LdapException;


class LdapService
{
    private $ldap;
    private $dn;
    private $attributes;

    function __construct($host, $dn, $cn, $password)
    {
        $this->ldap = Ldap::create(
            'ext_ldap',
            array(
                'host' => $host,
            )
        );

        $this->dn = $dn;
        $this->ldap->bind($cn, $password);
        $this->attributes = array(
            'samaccountname',
            'displayname',
            'mail'
        );
    }

    public function getEntry($username)
    {
        
        $filter = "(&(|(sAMAccountName=*$username*))(objectClass=User))";

        $query = $this->ldap->query($this->dn, $filter , ['maxItems' => 1, 'filter' => $this->attributes]);
        $results = $query->execute()->toArray();
        return count($results) > 0 ? $results[0] : null;
    }

    public function bind($dn, $password)
    {
        return $this->ldap->bind($dn, $password);
    }

    public function search($filter)
    {
        $query = $this->ldap->query($this->dn, $filter, ['filter' => $this->attributes]);

        $result = $query->execute()->toArray();

        $oResults = array_map(function ($x) {
            $userEntryArray = array_map(function ($y) {
                return $y[0];
            },  $x->getAttributes());
            return array_change_key_case($userEntryArray, CASE_LOWER);
        }, $result);

        return $oResults;
    }

    public function getLdap()
    {
        return $this->ldap;
    }

    public function getDn()
    {
        return $this->dn;
    }
}