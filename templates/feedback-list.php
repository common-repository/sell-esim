<div class="wrap">
    <h1>Feedback List</h1>
    <?php

    settings_errors();
    global $wpdb;
    $request = new \WP_REST_Request();
    $current_page = max(1, $_GET['paged']??1); // Get current page number
    $per_page = 50; // Number of items per page
    $offset = ($current_page - 1) * $per_page; // Offset for query
    $feedback_list = \tsim\expend\helper\DbHelper::name('tsim_feedback_list')->limit($per_page,$current_page)->select();
    ?>
    <table class="widefat">
        <thead>
        <tr>
            <th><?php echo esc_html('ID'); ?></th>
            <th><?php echo esc_html('Name'); ?></th>
            <th><?php echo esc_html('Email'); ?></th>
            <th><?php echo esc_html('Content'); ?></th>
            <th><?php echo esc_html('Create Time'); ?></th>
        </tr>
        </thead>
        <?php
        if ($feedback_list) {
            foreach ($feedback_list as $feedback) {
                echo '
                    <tr>
                        <td>' . esc_html($feedback->id) . '</td>
                        <td>' . esc_html($feedback->name) . '</td>
                        <td>' . esc_html($feedback->email) . '</td>
                        <td  style="word-wrap: break-word; max-width: 300px;">' . (esc_html($feedback->content)) . '</td>
                        <td>' . esc_html($feedback->create_time) . '</td>
                    </tr>
                    ';
            }
        }
        ?>
    </table>
    <?php
    // Pagination
    $table_name = $wpdb->prefix . 'tsim_feedback_list';
    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name"); // Total number of items
    $total_pages = ceil($total_items / $per_page); // Calculate total pages
    $pagination_args = array(
        'base' => esc_url(add_query_arg('paged', '%#%')),
        'format' => '',
        'total' => $total_pages,
        'current' => $current_page,
        'prev_text' => __('&laquo; Previous'),
        'next_text' => __('Next &raquo;'),
    );

    echo '<div class="pagination">' . paginate_links($pagination_args) . '</div>'
    ?>
</div>