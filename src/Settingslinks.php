<?php

namespace TsimAboy\SellEsim;

class Settingslinks
{

    public function register()
    {
        add_filter("plugin_action_links_" . SELLESIM_PLUGIN, array($this, 'settings_link'));
    }

    public function settings_link($links)
    {
        $links[] = '<a href="admin.php?page=sell_esim">' . __('Settings') . '</a>';
        $links[] = '<a href="https://b2b.tsimtech.com/" target="_blank">' . __('My Account') . '</a>';
        return $links;
    }
}
