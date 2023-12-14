<?php
	$rdata = array_map('to_utf8', array_map('safe_html', array_map('html_attr_tags_ok', $rdata)));
	$jdata = array_map('to_utf8', array_map('safe_html', array_map('html_attr_tags_ok', $jdata)));
?>
<script>
	$j(function() {
		var tn = 'Donations';

		/* data for selected record, or defaults if none is selected */
		var data = {
			SupporterID: <?php echo json_encode(['id' => $rdata['SupporterID'], 'value' => $rdata['SupporterID'], 'text' => $jdata['SupporterID']]); ?>,
			CampaignID: <?php echo json_encode(['id' => $rdata['CampaignID'], 'value' => $rdata['CampaignID'], 'text' => $jdata['CampaignID']]); ?>,
			MailingName: <?php echo json_encode($jdata['MailingName']); ?>,
			Address1: <?php echo json_encode($jdata['Address1']); ?>,
			Address2: <?php echo json_encode($jdata['Address2']); ?>,
			City: <?php echo json_encode($jdata['City']); ?>,
			State: <?php echo json_encode($jdata['State']); ?>,
			Zip: <?php echo json_encode($jdata['Zip']); ?>,
			Country: <?php echo json_encode($jdata['Country']); ?>
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

		/* saved value for SupporterID autofills */
		cache.addCheck(function(u, d) {
			if(u != tn + '_autofill.php') return false;

			for(var rnd in d) if(rnd.match(/^rnd/)) break;

			if(d.mfk == 'SupporterID' && d.id == data.SupporterID.id) {
				$j('#MailingName' + d[rnd]).html(data.MailingName);
				$j('#Address1' + d[rnd]).html(data.Address1);
				$j('#Address2' + d[rnd]).html(data.Address2);
				$j('#City' + d[rnd]).html(data.City);
				$j('#State' + d[rnd]).html(data.State);
				$j('#Zip' + d[rnd]).html(data.Zip);
				$j('#Country' + d[rnd]).html(data.Country);
				return true;
			}

			return false;
		});

		/* saved value for CampaignID */
		cache.addCheck(function(u, d) {
			if(u != 'ajax_combo.php') return false;
			if(d.t == tn && d.f == 'CampaignID' && d.id == data.CampaignID.id)
				return { results: [ data.CampaignID ], more: false, elapsed: 0.01 };
			return false;
		});

		cache.start();
	});
</script>

