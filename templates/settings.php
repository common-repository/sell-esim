<div class="wrap">
    <h1>Sell eSIM Plugin</h1>
    <?php 
        settings_errors();
    ?>
    
    <form method="post" action="options.php">
        <?php 
            settings_fields('sellesim_settings_options_group');
            do_settings_sections('sellesim_setting');
            submit_button();
        ?>
    </form>
</div>