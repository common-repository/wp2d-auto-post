<h2>WP2D</h2>

<form action="options.php" method="post">

<?php
settings_fields(
    'wp2d_plugin_options'   // option group from register_settigns
);
do_settings_sections(
    'wp2d_plugin'       // page id
);
?>
    <input name="submit" class="button button-primary" type="submit" value=" <?php echo __('Save') ?> ">
</form>
<p>
<hr>
<h4> How to get Discord channel webhook URL</h4>
<ol>
<li>Open your Discord application</li>
<li>Switch to your server.</li>
<li>Press the "Edit Channel" button to the right of the channel name.</li>
<a href="<?php echo plugin_dir_url(__FILE__) ?>/img/vp2d-howto-01-1200x600.jpg" target=_blank>
    <img src="<?php echo plugin_dir_url(__FILE__) ?>/img/vp2d-howto-01-1200x600.jpg" width=650>
</a>
<li>Switch to the "Integration" tab.</li>
<li>Press the "Create Webhook" button.</li>
<a href="<?php echo plugin_dir_url(__FILE__) ?>/img/vp2d-howto-02-1200x600.jpg" target=_blank>
    <img src="<?php echo plugin_dir_url(__FILE__) ?>/img/vp2d-howto-02-1200x600.jpg" width=650>
</a>
<li>Press the "Copy Webhook URL" button. This will copy the required Webhook URL to the Clipboard.</li>
<a href="<?php echo plugin_dir_url(__FILE__) ?>/img/vp2d-howto-03-1200x600.jpg" target=_blank>
    <img src="<?php echo plugin_dir_url(__FILE__) ?>/img/vp2d-howto-03-1200x600.jpg" width=650>
</a>
<li>Now you can paste (ctrl + v) it to the settings field.</li>
</ol>
<p>

