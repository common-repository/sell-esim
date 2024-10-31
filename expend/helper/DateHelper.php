<?php

namespace tsim\expend\helper;
class DateHelper
{
    public static function utf8ToGmt($east8_date, $format = 'Y-m-d H:i:s')
    {
        $east8_timezone = new \DateTimeZone('Asia/Shanghai');
//        $wp_timezone_string = get_option('timezone_string');
//        if (empty($wp_timezone_string)) {
        $wp_timezone_offset = get_option('gmt_offset');
        $wp_timezone_string = timezone_name_from_abbr('', $wp_timezone_offset * 3600, 0);
//        }
        if ($wp_timezone_string === false) {
            $wp_timezone_string = 'UTC';
        }
        $wp_timezone = new \DateTimeZone($wp_timezone_string);

        $date = new \DateTime($east8_date, $east8_timezone);

        $date->setTimezone($wp_timezone);

        return $date->format($format);
    }
    public static function datetimeToTimestampSH($shanghai_time_str, $time_format = 'Y-m-d H:i:s') {
        $shanghai_timezone = new \DateTimeZone('Asia/Shanghai');

        $date = \DateTime::createFromFormat($time_format, $shanghai_time_str, $shanghai_timezone);
        if (!$date) {
            return 0;
        }

        return $date->getTimestamp();
    }

    public static function timestampToLocalTime($timestamp = 0, $format = 'Y-m-d H:i:s')
    {
        if (empty($timestamp)) {
            $timestamp = time();
        }
        $wp_timezone_string = get_option('timezone_string');

        if (empty($wp_timezone_string)) {
            $wp_timezone_offset = get_option('gmt_offset');
            $wp_timezone_string = timezone_name_from_abbr('', $wp_timezone_offset * 3600, 0);

            if ($wp_timezone_string === false) {
                $wp_timezone_string = 'UTC';
            }
        }

        $wp_timezone = new \DateTimeZone($wp_timezone_string);

        $date = new \DateTime("@$timestamp");

        $date->setTimezone($wp_timezone);

        return $date->format($format);
    }

    public static function gmtToLocalDate($gmt_date, $format = 'Y-m-d H:i:s')
    {
        // 获取WordPress的时区设置
        $wp_timezone_string = get_option('timezone_string');

        if (empty($wp_timezone_string)) {
            // 如果没有设置具体时区字符串，使用时区偏移
            $wp_timezone_offset = get_option('gmt_offset');
            $wp_timezone_string = timezone_name_from_abbr('', $wp_timezone_offset * 3600, 0);

            // 在某些情况下，timezone_name_from_abbr 可能返回 false，因此我们需要一个备用方案
            if ($wp_timezone_string === false) {
                $wp_timezone_string = 'UTC';
            }
        }
        // 创建GMT的时区对象
        $gmt_timezone = new \DateTimeZone('GMT');

        // 创建WordPress设置的时区对象
        $wp_timezone = new \DateTimeZone($wp_timezone_string);

        // 创建基于GMT日期的DateTime对象
        $date = new \DateTime($gmt_date, $gmt_timezone);

        // 设置DateTime对象的时区为WordPress设置的时区
        $date->setTimezone($wp_timezone);

        // 返回转换后的日期和时间
        return $date->format('Y-m-d H:i:s');
    }


}