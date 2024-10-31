<?php

namespace TsimAboy\SellEsim;

class Admin
{
    public $wpapi;
    public $pages;
    public $subpages;
    public $callback;

    public function __construct()
    {
        $this->wpapi = new Wpapi();
        $this->callback = new Admincallback();
    }

    public function register()
    {
        $this->set_pages();
        $this->set_subpages();
        
        $this->wpapi
            ->add_pages($this->pages)
            ->with_subpage(__('Settings'))
            ->add_subpages($this->subpages)
            ->register();
    }

    public function set_pages(){
        $this->pages = [
            [
                'page_title' => __('Settings'),
                'menu_title' => 'Sell eSIM',
                'capability' => 'manage_options',
                'menu_slug' => 'sell_esim',
                'callback' => array($this->callback, 'settings_tpl'),
                'icon_url' => 'dashicons-admin-site-alt2',
                'position' => 100,
            ]
        ];
    }

    public function set_subpages(){
        $this->subpages = [
            [
                'parent_slug' => 'sell_esim',
                'page_title' => __('eSIM Dataplan'),
                'menu_title' => __('eSIM Dataplan'),
                'capability' => 'manage_options',
                'menu_slug' => 'sellesim_dataplan_list',
                'callback' => array($this->callback, 'list_tpl'),
            ],
            [
                'parent_slug' => 'sell_esim',
                'page_title' => __('Feedback List'),
                'menu_title' => __('Feedback List'),
                'capability' => 'manage_options',
                'menu_slug' => 'sellesim_feedback_list',
                'callback' => array($this->callback, 'list_feedback_tpl'),
            ],
        ];
        $this->_initSettingPage();
    }
    private function _initSettingPage()
    {
        $page = 'sellesim_setting';
        $setting = [
            'option_group' => 'sellesim_settings_options_group',
            'option_name' => 'sellesim_settings',
            'callback' => array($this->callback, 'settings_callback'),
        ];
        $this->wpapi->set_settings($setting);

        $section = [
            'id' => 'sellesim_settings_section',
            'title' => 'Settings',
            'callback' => array($this->callback, 'sections_callback'),
            'page' => $page
        ];
        $this->wpapi->set_sections($section);

        $fields = [
            [
                'id' => 'host',
                'title' => 'TSIM API HOST',
                'callback' => array($this->callback, 'fields_callback'),
                'page' => $page,
                'section' => $section['id'],
                'args' => [
                    'option_name' => $setting['option_name'],
                    'label_for' => 'host',
                    'text' => 'TSIM API HOST'
                ]
            ],
            [
                'id' => 'account',
                'title' => 'TSIM ACCOUNT',
                'callback' => array($this->callback, 'fields_callback'),
                'page' => $page,
                'section' => $section['id'],
                'args' => [
                    'option_name' => $setting['option_name'],
                    'label_for' => 'account',
                    'text' => 'TSIM-ACCOUNT'
                ]
            ],
            [
                'id' => 'secret',
                'title' => 'Secret',
                'callback' => array($this->callback, 'fields_callback'),
                'page' => $page,
                'section' => $section['id'],
                'args' => [
                    'option_name' => $setting['option_name'],
                    'label_for' => 'secret',
                    'text' => 'secret'
                ]
            ],
            [
                'id' => 'auto_send_email',
                'title' => 'Auto send email',
                'callback' => array($this->callback, 'fields_checkbox'),
                'page' => $page,
                'section' => $section['id'],
                'args' => [
                    'option_name' => $setting['option_name'],
                    'label_for' => 'auto_send_email',
                    'type' => 'checkbox', // 修改字段类型为单选按钮
                    'class' => 'radio',
                    'checkbox_value_list' => [
                        ['value'=>1]
                    ]

                ]
            ],
            [
                'id' => 'update_time',
                'title' => '',
                'callback' => array($this->callback, 'fields_callback'),
                'page' => $page,
                'section' => $section['id'],
                'args' => [
                    'option_name' => $setting['option_name'],
                    'label_for' => 'update_time',
                    'type' => 'hidden'
                ]
            ]
        ];
        $this->wpapi->set_fields($fields);
    }

}
