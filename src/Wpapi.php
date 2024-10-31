<?php

namespace TsimAboy\SellEsim;

class Wpapi
{
    public $admin_pages = [];
    public $admin_subpages = [];
    public $settings = [];
    public $sections = [];
    public $fields = [];

    public function register()
    {
        if (!empty($this->admin_pages)) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
        }
        if (!empty($this->settings)) {
            add_action('admin_init', array($this, 'register_custom_fields'));
        }
    }

    public function add_pages(array $pages)
    {
        $this->admin_pages = $pages;
        return $this;
    }

    public function add_admin_menu()
    {
        foreach ($this->admin_pages as $page) {
            add_menu_page(
                $page['page_title'],
                $page['menu_title'],
                $page['capability'],
                $page['menu_slug'],
                $page['callback'],
                ($page['icon_url'] ?? ''),
                ($page['position'] ?? 100),
            );
        }
        foreach ($this->admin_subpages as $subpage) {
            add_submenu_page(
                $subpage['parent_slug'],
                $subpage['page_title'],
                $subpage['menu_title'],
                $subpage['capability'],
                $subpage['menu_slug'],
                $subpage['callback']
            );
        }
    }

    public function with_subpage(string $title = null)
    {
        if (empty($this->admin_pages)) {
            return $this;
        }

        $admin_page = $this->admin_pages[0];
        $subpage = [
            [
                'parent_slug' => $admin_page['menu_slug'],
                'page_title' => $admin_page['page_title'],
                'menu_title' => ($title ?? $admin_page['menu_title']),
                'capability' => $admin_page['capability'],
                'menu_slug' => $admin_page['menu_slug'],
                'callback' => ($admin_page['callback'] ?? '')
            ]
        ];
        $this->admin_subpages = array_merge($this->admin_subpages, $subpage);
        return $this;
    }

    public function add_subpages(array $pages)
    {
        $this->admin_subpages = array_merge($this->admin_subpages, $pages);
        return $this;
    }

    public function register_custom_fields()
    {
        foreach ($this->settings as $setting) {
            register_setting($setting['option_group'], $setting['option_name'], ($setting['callback'] ?? ''));
        }
        foreach ($this->sections as $section) {
            add_settings_section($section['id'], $section['title'], ($section['callback'] ?? ''), $section['page']);
        }
        foreach ($this->fields as $field) {
            add_settings_field($field['id'], $field['title'], ($field['callback'] ?? ''), $field['page'], $field['section'], ($field['args'] ?? ''));
        }
    }

    public function set_settings(array $setting)
    {
        $this->settings[] = $setting;
        return $this;
    }

    public function set_sections(array $section)
    {
        $this->sections[] = $section;
        return $this;
    }

    public function set_fields(array $fields)
    {
        $this->fields = array_merge($this->fields, $fields);
        return $this;
    }

}
