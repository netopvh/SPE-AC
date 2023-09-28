<?php

namespace App\Classes;

use App\Utils\ConectionLDAP;

class LDAP {

    private $login;
    private $password;

    private $user = CONNECTION_LDAP['DN'];

    function setLogin($login){
        $this->login = $login;
    }

    function setPassword($password){
        $this->password = $password;
    }

    public function verify_login()
    {
        if(in_array(ambiente, ['local', 'develop'])) return true;

        try {   
            $result = ldap_search(ConectionLDAP::getInstance(), $this->user, "(uid=$this->login)");

            $entries = ldap_get_entries(ConectionLDAP::getInstance(), $result);
            $user = [];
            for ($users = 0; $users < $entries["count"]; $users++) {
                @$dn = $entries[$users]['dn'];
                $user = [
                    'name' => $entries[$users]['displayname'][0],
                    'uid' => $entries[$users]['uid'][0],
                    'mail' => $entries[$users]['mail'][0],
                ];
            }
            
            $username = @$dn;
            $upasswd = @$this->password;
            $ldapbind = @ldap_bind(ConectionLDAP::getInstance(), $username, $upasswd);

            ldap_close(ConectionLDAP::getInstance());
            
            if (!$ldapbind) {
                return false;
            } else {
                return $user;
            }

        } catch (\Throwable $th) {
            return false;
        }
           
    }  

    public function verify_user()
    {
        try {      

            $result = ldap_search(ConectionLDAP::getInstance(), $this->user, "(uid=$this->login)");

            $entries = ldap_get_entries(ConectionLDAP::getInstance(), $result);
            $user = [];
            for ($users = 0; $users < $entries["count"]; $users++) {
                @$dn = $entries[$users]['dn'];
                $user = [
                    'name' => $entries[$users]['displayname'][0],
                    'uid' => $entries[$users]['uid'][0],
                    'mail' => $entries[$users]['mail'][0],
                ];
            }

            ldap_close(ConectionLDAP::getInstance());
            
            return $user;
            
        } catch (\Throwable $th) {
            return false;
        }
           
    }
}