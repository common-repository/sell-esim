<?php

namespace tsim\expend\install;

class Table
{
    const VERSION = 263;
    const VERSIONKEY = 'tsim_table_create_version';

    protected static $_instance = null;

    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public  function init()
    {
        global $wpdb;
        $schema_version = self::VERSION;
        $vesion_key = self::VERSIONKEY;

        $db_schema_version = (int)get_option($vesion_key, 0);

//        if ($db_schema_version >= $schema_version && 0 !== $db_schema_version) {
//            return;
//        }
        $show_errors = $wpdb->hide_errors();
        $collate = $wpdb->has_cap('collation') ? $wpdb->get_charset_collate() : '';

        // 创建表的SQL语句
        $sql_list = [
            $wpdb->prefix . 'tsim_orders' =>" 
                CREATE TABLE   `{$wpdb->prefix}tsim_orders` (
                  `id` bigint(20) NOT NULL AUTO_INCREMENT,
                  `order_id` bigint(20) NOT NULL,
                  `tsim_topup_id` char(32)  NOT NULL,
                  `sku` varchar(50)  DEFAULT '',
                  `product_id` bigint(20) NOT NULL,
                  `product_name` varchar(255)  DEFAULT '',
                  `data_allowance` varchar(255)  DEFAULT '0',
                  `can_query` smallint(1) DEFAULT 1,
                  `user_id` bigint(20) DEFAULT 0,
                  `nums` int(11) DEFAULT 1,
                  `total_amount` decimal(11,2) DEFAULT 0.00,
                  `day` int(11) DEFAULT null,
                  `apn` varchar(255) DEFAULT null,
                  `create_time` int(11) DEFAULT 0,
                  `update_time`  int(11) DEFAULT 0,
                  PRIMARY KEY (`id`),
                  KEY `order_id` (`order_id`),
                  KEY `tsim_topup_id` (`tsim_topup_id`)
                ) {$collate};",
            $wpdb->prefix . 'tsim_data_plan_items' =>
                "CREATE TABLE  `{$wpdb->prefix}tsim_data_plan_items` (
                `id` bigint(20) NOT NULL AUTO_INCREMENT,
                `order_id` bigint(20) NOT NULL,
                `tsim_orders_id` bigint(20) NOT NULL ,
                `tsim_topup_id` char(32)  DEFAULT '',
                `iccid` char(32)  DEFAULT '',
                `lpa_str` varchar(255)  DEFAULT '',
                `qrcode` varchar(500)  DEFAULT '',
                `status` smallint(2) DEFAULT '0',
                `data_usage` bigint(64) DEFAULT '0',
                `total_usage` bigint(64) DEFAULT '0',
                `can_check` smallint(1) DEFAULT 1,
                `active_time`  int(11) DEFAULT 0,
                `expire_time`  int(11) DEFAULT 0,
                `create_time` int(11) DEFAULT 0,
                `update_time`  int(11) DEFAULT 0,
                `termination_time`  int(11) DEFAULT 0,
                `sort` int(11) DEFAULT '0', 
                PRIMARY KEY (`id`),
                KEY `order_id` (`order_id`),
                KEY `tsim_topup_id` (`tsim_topup_id`),
                KEY `tsim_orders_id` (`tsim_orders_id`)
                 ){$collate};",
             $wpdb->prefix . 'tsim_order_notice' =>"
             CREATE TABLE  `{$wpdb->prefix}tsim_order_notice` (
                `id` bigint(20) NOT NULL AUTO_INCREMENT,
                `tsim_orders_id` bigint(20) NOT NULL ,
                `data_plan_items_id` bigint(20) NOT NULL ,
                `event` char(32)  DEFAULT '',
                `ext_json` text  ,
                `user_id` bigint(20) DEFAULT 0,
                `event_time`  int(11) DEFAULT 0,
                PRIMARY KEY (`id`),
                KEY `tsim_orders_id` (`tsim_orders_id`)
                 ){$collate};",
             $wpdb->prefix . 'tsim_feedback_list' =>"
             CREATE TABLE  `{$wpdb->prefix}tsim_feedback_list` (
                `id` bigint(20) NOT NULL AUTO_INCREMENT,
                `user_id` bigint(20) DEFAULT 0,
                `name` varchar(255) DEFAULT '',
                `email` varchar(255) DEFAULT '',
                `content` text comment '' ,
                `create_time`  int(11) DEFAULT 0,
                PRIMARY KEY (`id`)
                 ){$collate};",

        ];

        // 执行SQL语句
        $flag = true;
        foreach($sql_list as $table_name=>$sql){
            $rs = $this->create_table($table_name,$sql);
//            error_log("create_sql:".$sql);
            if(!$rs){
                $flag = false;
            }
        }
        if($flag){
            update_option( $vesion_key, $schema_version );
        }
    }
    protected  function create_table( $table_name, $create_sql ) {
        global $wpdb;

        if ( in_array( $table_name, $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ), 0 ), true ) ) {
            return true;
        }

        $wpdb->query( $create_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        return in_array( $table_name, $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ), 0 ), true );
    }
}