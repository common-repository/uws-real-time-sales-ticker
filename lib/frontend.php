<?php
class UWSRealTimeSales_FrontEnd {
	
	// Next Version - 	setting to automatically add new IPs as they sign in as an admin
	// 			  		textarea to have "start over with just this IP address"
	// 					remove an IP from textarea that hasn't logged in as an admin in 14 days (advanced setting - auto-checked - strong recommendation to leave on) 
	
	public function __construct() {
		
		add_action('wp_footer', array($this, 'output' ));
		add_action('wp_ajax_nopriv_get_sales_data', array( $this, 'get_sales_data_callback'));
		
	}	
	
	public function output() {
		if (!$this->_showFrontEnd()) return; 

?>		
		<style>
			html { margin-top: 32px; }
			.uws-real-time-stats-bar { position: fixed; top: 0; left: 0; width: 100%; text-align: center; z-index: 99999; color: #ccc; font-size: 13px; line-height: 32px; background: #23282d; font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif; }
		</style>
		<div class="uws-real-time-stats-bar">
			<span class="">Today: </span><span id="uws-bar-real-time-orders">&nbsp;</span> 
			Order<span class="uws-bar-order-plural">s</span> / 
			<?php echo get_woocommerce_currency_symbol(); ?><span id="uws-bar-real-time-revenue"></span>
		</div>
<?php	
		$this->_outputScript();
	}
		
		
	public static function _getUserIP() {
		$userIP = '';
		
		if (isset($_SERVER['HTTP_ORIGINAL_IP']) && !empty($_SERVER['HTTP_ORIGINAL_IP']))
			$userIP = $_SERVER['HTTP_ORIGINAL_IP'];
		elseif (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP']))
			$userIP = $_SERVER['HTTP_CLIENT_IP'];
		elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR']))
			$userIP = $_SERVER['HTTP_X_FORWARDED_FOR'];
		elseif (isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR']))
			$userIP = $_SERVER['REMOTE_ADDR'];	
		
		return $userIP;
	}
	

	public function get_sales_data_callback() {	
						
		if (!$this->_showFrontEnd(true)) return; 
			
		$stats = UWSRealTimeSales::getStats();
		$stats = UWSRealTimeSales::normamlizeStats($stats);
		
		echo json_encode($stats);
	
		exit();
	
	}
	
	private function _showFrontEnd($ajax = false) {
				
		if (is_user_logged_in()) return false;  // this is only for not logged in

		if (!$ajax && is_admin()) return false;	// don't show in the Admin side (but need to if ajax call)	
		
		$frontEndIPs = $this->_getFrontEndIPs();
		
		return (in_array($this->_getUserIP(), $frontEndIPs));   // user does not match supplied front-end IPs
		
	}
	
	private function _getFrontEndIPs() {
		$setting = get_option('wc_uws_rts_frontend_ips');
		$lines = explode("\n", $setting);
		$ips = array();
		foreach ($lines as $line):
			$parts = explode('|', $line);
			$ips[] = trim($parts[0]);
		endforeach;
		
		return $ips;
	}
	
	private function _outputScript() {
		wp_localize_script( 'my_script_handle', 'my_ajaxurl', admin_url( 'admin-ajax.php' ) );
		
?>
		<script>
		(function($) {
			var uwsStatInterval = setInterval(updateUWSStats, <?php echo get_option('wc_uws_rts_check_interval', UWSRealTimeSalesSetup::DEFAULT_CHECK_INTERVAL); ?> * 1000);
			
			function updateUWSStats() {
				var data = { action: 'get_sales_data' };
				$.ajax({
					url: '/wp-admin/admin-ajax.php',
					method: 'POST',
					dataType: 'json',
					data: data,
					success: function(data) {
						
						$('#uws-bar-real-time-orders').text(data['orders']['today']);
						$('#uws-bar-real-time-revenue').text(data['sales']['today']);
						
						if (data['orders']['today'] == 1)
							$('.uws-bar-order-plural').hide();
						else
							$('.uws-bar-order-plural').show();
						
					}
				});
				
			}
			
			updateUWSStats();
		
		})(jQuery);
		</script>
<?php 
	}
}