(function($) {
	
	var uwsStatInterval = setInterval(updateUWSStats, [check_interval] * 1000);
	
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