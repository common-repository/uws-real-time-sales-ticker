<?php
/*
Plugin Name: UWS Real-Time Sales Ticker
Plugin URI: https://www.universalwebservices.net/products/
Description: Display real-time WooCommerce sales numbers as a ticker across the top of your WordPress admin or site.
Author: Universal Web Services, LLC
Version: 1.0
Author URI: https://www.universalwebservices.net/
Text Domain: uws-real-time-sales
WC requires at least: 2.6.0
WC tested up to: 4.9.2
*/


defined( 'ABSPATH' ) or die( 'Nice try!' );  // block direct access to script


require('lib/setup.php');
require('lib/frontend.php');

new UWSRealTimeSalesSetup(plugin_basename(__FILE__), plugin_dir_path( __FILE__ ));
new UWSRealTimeSales_FrontEnd();

class UWSRealTimeSales {
	
	public static function getStats() {
		
		// get a stats array of (type => date condition)
		
		// start with just yesterday sales
		$types = array('sales' => array('today'), 'orders' => array('today'));
		
		return self::_getSalesForPeriods(array('period_conditions' => $types));
		
	}

	public static function normamlizeStats($stats) {
		$sales = array('today' => 0);
		$orders = array('today' => 0);
		foreach ($stats as $stat):
			
			if ($stat['type'] === 'sales_today')
				$sales['today'] = $stat['value'];
			elseif ($stat['type'] === 'orders_today')
				$orders['today'] = $stat['value'];
			
		endforeach;
		
		return array('sales' => $sales, 'orders' => $orders);
	}
	
	private static function _periodToSQL($period) {
		
		if ($period === 'today'):
			return 'DATE(post_date) = DATE(NOW())';
		elseif ($period === 'yesterday'):
			return 'DATE(post_date) = DATE(NOW() - INTERVAL 1 DAY)';
		elseif ($period === '24hours'):
			return 'post_date > NOW() - INTERVAL 24 HOUR';
		endif;
		
	}
	
	private static function _getSalesForPeriods($args) {
		
		// array - type and date condition
		if (!isset($args['period_conditions'])) $args['period_conditions'] = array('sales' => array('today'));
		
		global $wpdb;
		
		$sql = '';
		foreach ($args['period_conditions'] as $type => $conditions):
			if (!in_array($type, array('sales', 'orders'))) return; // validity check
		
			foreach ($conditions as $condition):
				if (!in_array($condition, array('today', 'yesterday'))) return; // validity check
		
				$periodCondition = self::_periodToSQL($condition);
		
				switch ($type) {

				case 'orders':
					$sql .= <<<EOD

SELECT 		'{$type}_{$condition}' as type, COUNT(orders.ID) AS value
FROM 		{$wpdb->posts} AS orders
LEFT JOIN 	{$wpdb->postmeta} AS meta
	   ON 	orders.ID = meta.post_id AND meta.meta_key = '_order_total'
WHERE 		orders.post_type = 'shop_order'
AND 		orders.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold' )
AND			{$periodCondition}

UNION
EOD;
					break;
					
				case 'sales':
					$sql .= <<<EOD

SELECT 		'{$type}_{$condition}' as type, COALESCE(SUM(meta.meta_value), 0) AS value
FROM 		{$wpdb->posts} AS orders
LEFT JOIN 	{$wpdb->postmeta} AS meta
	   ON 	orders.ID = meta.post_id AND meta.meta_key = '_order_total'
WHERE 		orders.post_type = 'shop_order'
AND 		orders.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold' )
AND			{$periodCondition}

UNION
EOD;
					break;
				}
			
			endforeach;	

		endforeach;

		$sql = rtrim($sql, 'UNION');
		
		return $wpdb->get_results($sql, ARRAY_A );
		
	}
	
	
}