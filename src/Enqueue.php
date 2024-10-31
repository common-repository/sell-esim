<?php

namespace TsimAboy\SellEsim;

class Enqueue
{
    public function register(){
        add_action('admin_enqueue_scripts', array($this, 'enqueue'));
    }

    public function enqueue()
    {
        wp_enqueue_style('mypluginstyle', SELLESIM_PLUGIN_URL . 'assets/sell-esim-style.css');
        wp_enqueue_script('mypluginscript', SELLESIM_PLUGIN_URL . 'assets/sell-esim-script.js');
    }
}
