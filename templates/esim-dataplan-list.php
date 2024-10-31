<div class="wrap">
    <h1>eSIM Dataplan List</h1>
    <?php
        settings_errors();
        $dataplan_list = get_option('sellesim_dataplan_list');
    ?>

    <table class="widefat">
        <thead>
            <tr>
                <th><?php echo esc_html('SKU'); ?></th>
                <th><?php echo esc_html('eSIM Dataplan name'); ?></th>
                <th><?php echo esc_html('Day(s)'); ?></th>
                <th><?php echo esc_html('PRICE'); ?></th>
                <th><?php echo esc_html('CURRENCY'); ?></th>
                <th><?php echo esc_html('APN'); ?></th>
            </tr>
        </thead>
        <?php
            if($dataplan_list){
                foreach ($dataplan_list as $dataplan) {
                    echo '
                    <tr>
                        <td>' . esc_html($dataplan['channel_dataplan_id']) . '</td>
                        <td>' . esc_html($dataplan['channel_dataplan_name']) . '</td>
                        <td>' . esc_html($dataplan['day']) . '</td>
                        <td>' . esc_html($dataplan['price']) . '</td>
                        <td>' . esc_html($dataplan['currency']) . '</td>
                        <td>' . esc_html($dataplan['apn']) . '</td>
                    </tr>
                    ';
                }
            }
        ?>
    </table>

</div>