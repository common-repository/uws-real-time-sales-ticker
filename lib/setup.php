<?php

class UWSRealTimeSalesSetup {
	
	const DEFAULT_CHECK_INTERVAL = 60;
	private $pluginDirPath = '';
	
	function __construct($pluginBaseName, $pluginDirPath) {
		$this->pluginDirPath = $pluginDirPath;
		
		add_action( 'admin_bar_menu', array( $this, 'addToAdminBar' ), 999 );
	
		add_action('wp_ajax_get_sales_data', array( $this, 'get_sales_data_callback'));
		
		add_action('admin_footer', array( $this, 'uws_realtime_footer'));
		add_action('wp_footer', array( $this, 'uws_realtime_footer'));
		
		add_filter( 'plugin_action_links_' . $pluginBaseName, array($this, 'wc_uws_rts_plugin_actions'), 10, 4);
		
		add_action( 'admin_menu', array($this, 'wc_uws_rts_menu'));
		add_action( 'admin_init', array($this, 'wc_uws_rts_register_settings' ));
	}

	public function wc_uws_rts_menu() {
		if (current_user_can('manage_options')):
			add_options_page(__("UWS Real-Time Sales Ticker", 'wc-uws-rts'), __("UWS Real-Time Sales Ticker", 'wc-uws-rts'), 'manage_options', 'wc-uws-rts-options', array($this, 'wc_uws_rts_options'));
		endif;
	}
	public function wc_uws_rts_plugin_actions( $actions, $plugin_file, $plugin_data, $context ) {
		array_unshift($actions, "<a href=\"".menu_page_url('wc-uws-rts-options', false)."\">".esc_html__("Settings")."</a>");
		return $actions;
	}

	public function wc_uws_rts_register_settings() {
		register_setting( 'wc_uws_rts_group', 'wc_uws_rts_check_interval', array('type' => 'integer', 'default' => self::DEFAULT_CHECK_INTERVAL, 'sanitize_callback' => array($this, 'wc_uws_rts_check_interval_validate')));
		register_setting( 'wc_uws_rts_group', 'wc_uws_rts_frontend_ips', array('type' => 'integer', 'default' => self::DEFAULT_CHECK_INTERVAL, 'sanitize_callback' => array($this, 'wc_uws_rts_frontend_ips_validate')));
	}

	public function wc_uws_rts_check_interval_validate( $input ) {
	 
		
		$output = intval( $input );

		if ($output < 15):
			add_settings_error( 'wc_uws_rts_check_interval', 'error-wc-uws-rts-check-interval', 'Minimum check interval is 15 seconds.', 'error');
			$output = 15;
		endif;
		
		// Return the array processing any additional functions filtered by this action
	    return apply_filters( 'wc_uws_rts_check_interval_validate', $output, $input );
	 
	}
	public function wc_uws_rts_frontend_ips_validate( $input ) {
	 
		$output = sanitize_textarea_field( $input );

		// Return the array processing any additional functions filtered by this action
	    return apply_filters( 'wc_uws_rts_frontend_ips_validate', $output, $input );
	 
	}
	
	public function wc_uws_rts_options() {
		add_thickbox();
		
		$checkInterval = get_option('wc_uws_rts_check_interval');	
		$frontEndIPs = get_option('wc_uws_rts_frontend_ips');	
	?>
	<style>
		.field { margin: 16px 0; font-size: 16px; }
		.field input[type=text], .field input[type=number] { height: auto; padding: 8px; font-size: 16px; }
		
		.field span, .field label input, .field label textarea { display: inline-block; margin: 4px 0; vertical-align: middle; box-sizing: border-box; } 
		.field label span { width: 300px; }
		.field span.description { font-size: 16px; vertical-align: middle; }
		.field .extended-description { display: none; margin: 8px 0; }
		.field textarea { width: 30%; min-width: 256px; height: 64px; }
		.field textarea[name=wc_uws_rts_frontend_ips] { width: 30%; min-width: 256px;  height: 96px; }
		.activate-extended-description { cursor: pointer; }
		.extended-description a { text-decoration: none; }
		.extended-description a:hover { text-decoration: underline; }
		
		.form-controls { margin-top: 64px; }
	</style>
		
	<div class="wrap">
		<div id="icon-options-general" class="icon32">
			<br>
		</div>
		<h1><?php esc_html_e("UWS Real-Time Sales Ticker", 'wc-uws-rts'); ?></h1>
		
		<form method="post" action="options.php">
			<?php settings_fields('wc_uws_rts_group'); ?>
	
			<h3>Real Time Stats Ticker Options</h3>
			<div class="field">
				<label>
					<span><?php esc_html_e("Check Interval", 'wc-uws-rts'); ?></span>
					<input type="number" name="wc_uws_rts_check_interval" size="4" min="15" max="9999" value="<?php echo esc_attr(get_option('wc_uws_rts_check_interval', self::DEFAULT_CHECK_INTERVAL)); ?>"> seconds
				</label>
				<span class="description"><a class="dashicons dashicons-editor-help activate-extended-description"></a></span>
				<p class="extended-description"><?php echo __('The check interval determines how often the Real-Time Sales Ticker is updated. The default and recommended value is 60 seconds. We don\'t recommend any value below 30 seconds. The minimum is 15 seconds.', 'wc-ga-ee'); ?></p>
			</div>

			<div class="field">
				<label>
					<span><?php esc_html_e("Front End IPs", 'wc-uws-rts'); ?></span>
					<textarea name="wc_uws_rts_frontend_ips"><?php echo esc_html(get_option('wc_uws_rts_frontend_ips')); ?></textarea>
				</label>
				<span class="description"><a class="dashicons dashicons-editor-help activate-extended-description"></a></span>
				<p class="extended-description"><?php echo __('Real-Time Sales can be shown on your website\'s front-end, without logging in! However, this must be limited by IP address so the whole world doesn\'t see your sales information.<br><br>Enter one IP address per line. Your IP address is <b>' . UWSRealTimeSales_FrontEnd::_getUserIP() . '</b><br><br>Note: You can use the | (pipe) symbol to leave a note about each IP address. ex: <code>' . UWSRealTimeSales_FrontEnd::_getUserIP() . ' | Admin\'s IP</code>', 'wc-ga-ee'); ?></p>
			</div>
			
			<div class="form-controls">
				<input type="submit" class="button-primary" value="<?php esc_html_e('Save Changes', 'wc-uws-rts') ?>">
			</div>
		</form>
	</div>
	
	<script>
	
	</script>
	
	<?php
	}



	public function addToAdminBar( $wp_admin_bar ) {
		$args = array(
			'id'     => 'uws-real-time-stats',
			'title'  => '<span class="">Today: </span><span id="uws-bar-real-time-orders">&nbsp;</span> Order<span class="uws-bar-order-plural">s</span> / ' . get_woocommerce_currency_symbol() . '<span id="uws-bar-real-time-revenue"></span>',
			'parent' => false
		);
		$wp_admin_bar->add_node( $args );
	}
	
	public function get_sales_data_callback() {	
			
		if( !$this->_allow() ) return;
			
		$stats = UWSRealTimeSales::getStats();
		$stats = UWSRealTimeSales::normamlizeStats($stats);
		
		echo json_encode($stats);
	
		exit();
	
	}
	
	public function uws_realtime_footer() {

		if( !$this->_allow() ) return;

		echo '<script>';
		$js = file_get_contents($this->pluginDirPath . 'src/admin.js');
		
		$js = str_replace('[check_interval]', get_option('wc_uws_rts_check_interval', self::DEFAULT_CHECK_INTERVAL), $js);
		
		$js = preg_replace('/\/\*((?!\*\/).)*\*\//', '', $js);
		$js = str_replace("\n", '', $js);
		$js = str_replace('; ', ';', $js);
		echo $js;
		echo '</script>';		
	}
	
	private function _allow() {
		if( !current_user_can('editor') && !current_user_can('administrator') ) return false;
		
		return true;		
	}
	
}