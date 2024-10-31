<?php

namespace TsimAboy\SellEsim;

class Admincallback
{

    public function settings_tpl()
    {
        require_once SELLESIM_PLUGIN_PATH . 'templates/settings.php';
    }

    public function list_tpl()
    {
        require_once SELLESIM_PLUGIN_PATH . 'templates/esim-dataplan-list.php';
    }
    public function list_feedback_tpl()
    {
        require_once SELLESIM_PLUGIN_PATH . 'templates/feedback-list.php';
    }

    public function settings_callback($input)
    {
        return $input;
    }

    public function sections_callback()
    {
        echo esc_html('API configuration');
    }

    public function fields_callback($args)
    {
        $name = $args['label_for'] ?? '';
        $text = $args['text'] ?? '';
        $option_name = $args['option_name'];
        $classes = $args['class'] ?? 'sellesim-class';
        $type = $args['type'] ?? 'text';
        $value = get_option($option_name)[$name] ?? '';
        if ($name == 'update_time') {
            $value = date('Y-m-d H:i:s');
        }
        echo '<input type="' . esc_attr($type) . '" class="' . esc_attr($classes) . '" name="' . esc_attr($option_name) . '[' . esc_attr($name) . ']" value="' . esc_attr($value) . '" placeholder="' . esc_attr($text) . '">';
    }

    public function fields_checkbox($args)
    {
        $name = $args['label_for'] ?? '';
        $text = $args['text'] ?? '';
        $option_name = $args['option_name'];
        $classes = $args['class'] ?? 'sellesim-class';
        $checkbox_value_list = $args['checkbox_value_list'] ?? [];
        $option_value = get_option($option_name)[$name] ?? '';
        $str = "";
        foreach ($checkbox_value_list as $val) {
            $value = $val['value'];
            $title = $val['title']??'';
            $checked = "";
            if($value == $option_value){
                $checked = "checked";
            }
            $str .= $title.' <input type="checkbox" '.$checked.' class="' . esc_attr($classes) . '" name="' . esc_attr($option_name) . '[' . esc_attr($name) . ']" value="' . esc_attr($value) . '" placeholder="' . esc_attr($text) . '">';
        }
        echo $str;
    }

}
