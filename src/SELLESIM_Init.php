<?php

namespace TsimAboy\SellEsim;

use tsim\expend\install\Table;

class SELLESIM_Init
{
    /**
     * Store all the classes inside an array
     * @return array FUll list of classes
     */
    public static function get_services()
    {
        return [
            Admin::class,
            Enqueue::class,
            Settingslinks::class,
            ApiClient::class,
//            RestApi::class,
        ];
    }

    /**
     * Loop through the classes,initailize them
     * call the register() method if it exists
     * @return void
     */
    public static function register_servivces()
    {
        foreach (self::get_services() as $class) {
            $service = self::getInstance($class);
            if (method_exists($service, 'register')) {
                $service->register();
            }
        }
    }

    /**
     * Initailize the class
     * @param class $class
     * @return object
     */
    private static function getInstance($class)
    {
        $service  = new $class();
        return $service;
    }

    public static function activate()
    {
        // flush rewrite rules
        (Table::instance())->init();
        flush_rewrite_rules();
    }

    public static function deactivate()
    {
        // flush rewrite rules
        flush_rewrite_rules();
    }

}
