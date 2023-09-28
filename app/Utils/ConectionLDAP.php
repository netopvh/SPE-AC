<?php

namespace App\Utils;

class ConectionLDAP { 
    
    private static $instance;

    public static $host = CONNECTION_LDAP['host'];
    public static $port = CONNECTION_LDAP['port'];

    public static function getInstance() {
        if (!isset(self::$instance)) {
            try {
                self::$instance = ldap_connect('ldap://' . self::$host . ':' . self::$port) or die('Não foi possível conectar ao servidor LDAP.');
            
                ldap_set_option(self::$instance, LDAP_OPT_PROTOCOL_VERSION, 3);
                ldap_set_option(self::$instance, LDAP_OPT_REFERRALS, 0);

            } catch (\Throwable $th) {
                echo $th->getMessage();
            }
        }
        return self::$instance;
    }
}