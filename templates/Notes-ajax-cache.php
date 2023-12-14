<?php
	$rdata = array_map('to_utf8', array_map('safe_html', array_map('html_attr_tags_ok', $rdata)));
	$jdata = array_map('to_utf8', array_map('safe_html', array_map('html_attr_tags_ok', $jdata)));
?>
<script>
	$j(function() {
		var tn = 'Notes';

		/* data for selected record, or defaults if none is selected */
		var data = {
			SupporterID: <?php echo json_encode(['id' => $rdata['SupporterID'], 'value' => $rdata['SupporterID'], 'text' => $jdata['SupporterID']]); ?>
		};

		/* initialize or continue using AppGini.cache for the current table */
		AppGini.cache = AppGini.cache || {};
		AppGini.cache[tn] = AppGini.cache[tn] || AppGini.ajaxCache();
		var cache = AppGini.cache[tn];

		/* saved value for SupporterID */
		cache.addCheck(function(u, d) {
			if(u != 'ajax_combo.php') return false;
			if(d.t == tn && d.f == 'SupporterID' && d.id == data.SupporterID.id)
				return { results: [ data.SupporterID ], more: false, elapsed: 0.01 };
			return false;
		});

		cache.start();
	});
</script>

