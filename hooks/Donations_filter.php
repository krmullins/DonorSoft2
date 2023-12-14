<?php 
	if(!isset($Translation)) { @header('Location: index.php'); exit; }

	$advanced_search_mode = 0;
	$search_mode_session_key = substr('spm_' . basename(__FILE__), 0, -4);
	if(Request::has('advanced_search_mode')) {
		/* if user explicitly sets search mode by clicking Filter_x from the filters page, 
		 * apply requested mode, and store into session */
		$advanced_search_mode = intval(Request::val('advanced_search_mode')) ? 1 : 0;
		$_SESSION[$search_mode_session_key] = $advanced_search_mode;

	} elseif(isset($_SESSION[$search_mode_session_key])) {
		/* otherwise, check if search mode for given table is specified in user's 
		 * session and apply it */
		$advanced_search_mode = intval($_SESSION[$search_mode_session_key]) ? 1 : 0;
	}
?>

	<input type="hidden" name="advanced_search_mode" value="<?php echo $advanced_search_mode; ?>" id="advanced_search_mode">
	<script>
		$j(function(){
			$j('.btn.search_mode').appendTo('.page-header h1');
			$j('.btn.search_mode').click(function(){
				var mode = parseInt($j('#advanced_search_mode').val());
				$j('#advanced_search_mode').val(1 - mode);
				if(typeof(beforeApplyFilters) == 'function') beforeApplyFilters();
				return true;
			});
		})
	</script>

<?php if($advanced_search_mode) { ?>
	<button class="btn btn-lg btn-success pull-right search_mode" id="simple_search_mode" type="submit" name="Filter_x" value="1">Switch to simple search mode</button>
	<script>
		$j(function() {
			$j('#simple_search_mode').click(function() {
				if(!confirm('If you switch to simple search mode, any filters defined here will be lost! Do you still which to proceed?')) return false;
				$j('.clear_filter').click();
			})		
		})
	</script>
	<?php include(__DIR__ . '/../defaultFilters.php'); ?>
	
<?php } else { ?>

	<button class="btn btn-lg btn-default pull-right search_mode" type="submit" name="Filter_x" value="1">Switch to advanced search mode</button>
			
			<!-- load bootstrap datetime-picker -->
			<link rel="stylesheet" href="resources/bootstrap-datetimepicker/bootstrap-datetimepicker.css">
			<script type="text/javascript" src="resources/moment/moment-with-locales.min.js"></script>
			<script type="text/javascript" src="resources/bootstrap-datetimepicker/bootstrap-datetimepicker.min.js"></script>
			
			
			<div class="page-header"><h1>
				<a href="Donations_view.php" style="text-decoration: none; color: inherit;">
					<img src="resources/table_icons/32Px - 385.png"> 					Donations Filters</a>
			</h1></div>

		
	<div class="row vspacer-lg" style="border-bottom: dotted 2px #DDD;" >
		
		<div class="hidden-xs hidden-sm col-md-3 vspacer-lg text-right"><label for="">Donation Date</label></div>
		<div class="hidden-md hidden-lg col-xs-12 vspacer-lg"><label for="">Donation Date</label></div>
		
		
		<div class="col-xs-3 col-md-1 vspacer-lg text-center">Between</div>
		
		<input type="hidden" name="FilterAnd[1]" value="and">
		<input type="hidden" name="FilterField[1]" value="2">   
		<input type="hidden" name="FilterOperator[1]" value="greater-than-or-equal-to">
		
		<div class="col-xs-9 col-md-3 col-lg-2 vspacer-md">
			<input type="text"  class="form-control" id="from-date_2"  name="FilterValue[1]" value="<?php echo htmlspecialchars($FilterValue[1]); ?>" size="10">
		</div>

				<div class="col-xs-3 col-md-1 text-center vspacer-lg"> and </div>
		
		<input type="hidden" name="FilterAnd[2]" value="and">
		<input type="hidden" name="FilterField[2]" value="2">  
		<input type="hidden" name="FilterOperator[2]" value="less-than-or-equal-to">
		
		<div class="col-xs-9 col-md-3 col-lg-2 vspacer-md">
			<input type="text" class="form-control" id="to-date_2" name="FilterValue[2]" value="<?php echo htmlspecialchars($FilterValue[2]); ?>" size="10">
		</div>
		
		
		<div class="col-xs-3 col-xs-offset-9 col-md-offset-0 col-md-1">
			<button type="button" class="btn btn-default vspacer-md btn-block" title='Clear fields' onclick="clearFilters($j(this).parent());" ><span class="glyphicon glyphicon-trash text-danger"></button>
		</div>

			</div>

				
	<script>
		$j(function(){
			//date
			$j("#from-date_2 , #to-date_2 ").datetimepicker({
				format: 'MM/DD/YYYY'
			});

			$j("#from-date_2" ).on('dp.change' , function(e){
				date = moment(e.date).add(1, 'month');  
				$j("#to-date_2 ").val(date.format('MM/DD/YYYY')).data("DateTimePicker").minDate(e.date);
			});
		});
	</script>

		
			<!-- ########################################################## -->
			
	<div class="row vspacer-lg" style="border-bottom: dotted 2px #DDD;" >
		
		<div class="hidden-xs hidden-sm col-md-3 vspacer-lg text-right"><label for="">Amount</label></div>
		<div class="hidden-md hidden-lg col-xs-12 vspacer-lg"><label for="">Amount</label></div>
		
					
		<div class="col-xs-3 col-md-1 vspacer-lg text-center">Between</div>
		<input type="hidden" name="FilterAnd[3]" value="and">
		<input type="hidden" name="FilterField[3]" value="3">   
		<input type="hidden" name="FilterOperator[3]" value="greater-than-or-equal-to">
		<div class="col-xs-9 col-md-3 col-lg-2 vspacer-md">
			<input type="text" class="numeric form-control" name="FilterValue[3]" value="<?php echo htmlspecialchars($FilterValue[3]); ?>" size="3">
		</div>

				<div class="col-xs-3 col-md-1 text-center vspacer-lg and"> and </div>
		<input type="hidden" name="FilterAnd[4]" value="and">
		<input type="hidden" name="FilterField[4]" value="3">  
		<input type="hidden" name="FilterOperator[4]" value="less-than-or-equal-to">
		<div class="col-xs-9 col-md-3 col-lg-2 vspacer-md">
			<input type="text" class="numeric form-control" name="FilterValue[4]" value="<?php echo htmlspecialchars($FilterValue[4]); ?>" size="3">
		</div>

		
		<div class="col-xs-3 col-xs-offset-9 col-md-offset-0 col-md-1">
			<button type="button" class="btn btn-default vspacer-md btn-block" title='Clear fields' onclick="clearFilters($j(this).parent());" ><span class="glyphicon glyphicon-trash text-danger"></button>
		</div>

			</div>

		
			<!-- ########################################################## -->
					
	<div class="row vspacer-lg" style="border-bottom: dotted 2px #DDD;" >
		
		<div class="hidden-xs hidden-sm col-md-3 vspacer-lg text-right"><label for="">Description</label></div>
		<div class="hidden-md hidden-lg col-xs-12 vspacer-lg"><label for="">Description</label></div>
		
				
		<input type="hidden" name="FilterAnd[6]" value="and">
		<input type="hidden" name="FilterField[6]" value="4">  
		<input type="hidden" name="FilterOperator[6]" value="like">
		<div class="col-md-8 col-lg-6 vspacer-md">
			<input type="text" class="form-control" name="FilterValue[6]" value="<?php echo htmlspecialchars($FilterValue[6]); ?>" size="3">
		</div>
		
		
		<div class="col-xs-3 col-xs-offset-9 col-md-offset-0 col-md-1">
			<button type="button" class="btn btn-default vspacer-md btn-block" title='Clear fields' onclick="clearFilters($j(this).parent());" ><span class="glyphicon glyphicon-trash text-danger"></button>
		</div>

			</div>


		
			<!-- ########################################################## -->
					

	<div class="row vspacer-lg" style="border-bottom: dotted 2px #DDD;">
		
		<div class="hidden-xs hidden-sm col-md-3 vspacer-lg text-right"><label for="">Supporter</label></div>
		<div class="hidden-md hidden-lg col-xs-12 vspacer-lg"><label for="">Supporter</label></div>
		
		
		<div class="col-md-8 col-lg-6 vspacer-md">
			<div id="filter_5" class="vspacer-lg"><span></span></div>

			<input type="hidden" class="populatedLookupData" name="7" value="<?php echo htmlspecialchars($FilterValue[7]); ?>" >
			<input type="hidden" name="FilterAnd[7]" value="and">
			<input type="hidden" name="FilterField[7]" value="5">  
			<input type="hidden" id="lookupoperator_5" name="FilterOperator[7]" value="equal-to">
			<input type="hidden" id="filterfield_5" name="FilterValue[7]" value="<?php echo htmlspecialchars($FilterValue[7]); ?>" size="3">
		</div>
		
		
		<div class="col-xs-3 col-xs-offset-9 col-md-offset-0 col-md-1">
			<button type="button" class="btn btn-default vspacer-md btn-block" title='Clear fields' onclick="clearFilters($j(this).parent());" ><span class="glyphicon glyphicon-trash text-danger"></button>
		</div>

			</div>

	<script>

		$j(function() {
			/* display a drop-down of categories that populates its content from ajax_combo.php */
			$j("#filter_5").select2({
				ajax: {
					url: 'ajax_combo.php',
					dataType: 'json',
					cache: true,
					data: function(term, page) {
						return {
							s: term,
							p: page,
							t: "Donations",
							f: "SupporterID",
							ut: 1,
							json: 1
						}; 
					},
					results: function (resp, page) { return resp; }
				}
			}).on('change', function(e){
				$j("#filterfield_5").val(e.added.text);
				$j("#lookupoperator_5").val('equal-to');
				if (e.added.id=='{empty_value}'){
					$j("#lookupoperator_5").val('is-empty');
				}
			});


			/* preserve the applied category filter and show it when re-opening the filters page */
			if ($j("#filterfield_5").val().length){
				
				//None case 
				if ($j("#filterfield_5").val() == '<None>'){
					$j("#filter_5").select2( 'data' , {
						id: '{empty-value}',
						text: '<None>'
					});
					$j("#lookupoperator_5").val('is-empty');
					return;
				}
				$j.ajax({
					url: 'ajax_combo.php',
					dataType: 'json',
					data: {
						s: $j("#filterfield_5").val(),  //search term
						p: 1,                                         //page number
						t: "Donations",                //table name
						f: "SupporterID",               //field name
						json: 1
					}
				}).done(function(response){
					if (response.results.length){
						$j("#filter_5").select2('data' , {
							id: response.results[1].id,
							text: response.results[1].text
						});
					}
				});
			}

		});
	</script>

		
			<!-- ########################################################## -->
					

	<div class="row vspacer-lg" style="border-bottom: dotted 2px #DDD;">
		
		<div class="hidden-xs hidden-sm col-md-3 vspacer-lg text-right"><label for="">Campaign</label></div>
		<div class="hidden-md hidden-lg col-xs-12 vspacer-lg"><label for="">Campaign</label></div>
		
		
		<div class="col-md-8 col-lg-6 vspacer-md">
			<div id="filter_6" class="vspacer-lg"><span></span></div>

			<input type="hidden" class="populatedLookupData" name="8" value="<?php echo htmlspecialchars($FilterValue[8]); ?>" >
			<input type="hidden" name="FilterAnd[8]" value="and">
			<input type="hidden" name="FilterField[8]" value="6">  
			<input type="hidden" id="lookupoperator_6" name="FilterOperator[8]" value="equal-to">
			<input type="hidden" id="filterfield_6" name="FilterValue[8]" value="<?php echo htmlspecialchars($FilterValue[8]); ?>" size="3">
		</div>
		
		
		<div class="col-xs-3 col-xs-offset-9 col-md-offset-0 col-md-1">
			<button type="button" class="btn btn-default vspacer-md btn-block" title='Clear fields' onclick="clearFilters($j(this).parent());" ><span class="glyphicon glyphicon-trash text-danger"></button>
		</div>

			</div>

	<script>

		$j(function() {
			/* display a drop-down of categories that populates its content from ajax_combo.php */
			$j("#filter_6").select2({
				ajax: {
					url: 'ajax_combo.php',
					dataType: 'json',
					cache: true,
					data: function(term, page) {
						return {
							s: term,
							p: page,
							t: "Donations",
							f: "CampaignID",
							ut: 1,
							json: 1
						}; 
					},
					results: function (resp, page) { return resp; }
				}
			}).on('change', function(e){
				$j("#filterfield_6").val(e.added.text);
				$j("#lookupoperator_6").val('equal-to');
				if (e.added.id=='{empty_value}'){
					$j("#lookupoperator_6").val('is-empty');
				}
			});


			/* preserve the applied category filter and show it when re-opening the filters page */
			if ($j("#filterfield_6").val().length){
				
				//None case 
				if ($j("#filterfield_6").val() == '<None>'){
					$j("#filter_6").select2( 'data' , {
						id: '{empty-value}',
						text: '<None>'
					});
					$j("#lookupoperator_6").val('is-empty');
					return;
				}
				$j.ajax({
					url: 'ajax_combo.php',
					dataType: 'json',
					data: {
						s: $j("#filterfield_6").val(),  //search term
						p: 1,                                         //page number
						t: "Donations",                //table name
						f: "CampaignID",               //field name
						json: 1
					}
				}).done(function(response){
					if (response.results.length){
						$j("#filter_6").select2('data' , {
							id: response.results[1].id,
							text: response.results[1].text
						});
					}
				});
			}

		});
	</script>

		
			<!-- ########################################################## -->
			
	<div class="row" style="border-bottom: dotted 2px #DDD;">
		
		<div class="hidden-xs hidden-sm col-md-3 vspacer-lg text-right"><label for="">Payment Type</label></div>
		<div class="hidden-md hidden-lg col-xs-12 vspacer-lg"><label for="">Payment Type</label></div>
		
		
		<input type="hidden" class="optionsData" name="FilterField[9]" value="7">
		<div class="col-xs-10 col-sm-11 col-md-8 col-lg-6">

			<input type="hidden" name="FilterAnd[9]" value="and">
			<input type="hidden" name="FilterOperator[9]" value="equal-to">
			<input type="hidden" name="FilterValue[9]" id="7_currValue" value="<?php echo htmlspecialchars($FilterValue[9]); ?>" size="3">

	
			<div class="radio">
				<label>
					 <input type="radio" name="FilterValue[9]" class="filter_7" value='Credit Card'>Credit Card				</label>
			</div>
	 
			<div class="radio">
				<label>
					 <input type="radio" name="FilterValue[9]" class="filter_7" value='Check'>Check				</label>
			</div>
	 
			<div class="radio">
				<label>
					 <input type="radio" name="FilterValue[9]" class="filter_7" value='Cash'>Cash				</label>
			</div>
	 
			<div class="radio">
				<label>
					 <input type="radio" name="FilterValue[9]" class="filter_7" value='Stock'>Stock				</label>
			</div>
	 		</div>

		
		<div class="col-xs-3 col-xs-offset-9 col-md-offset-0 col-md-1">
			<button type="button" class="btn btn-default vspacer-md btn-block" title='Clear fields' onclick="clearFilters($j(this).parent());" ><span class="glyphicon glyphicon-trash text-danger"></button>
		</div>

			</div>
	<script>
		//for population
		var filterValue_7 = '<?php echo htmlspecialchars($FilterValue[ 9 ]); ?>';
		$j(function () {
			if (filterValue_7) {
				$j("input[class =filter_7][value ='" + filterValue_7 + "']").prop("checked", true);
			}
		})
	</script>
			
			<!-- ########################################################## -->
			
	<div class="row" style="border-bottom: dotted 2px #DDD;">
		
		<div class="hidden-xs hidden-sm col-md-3 vspacer-lg text-right"><label for="">Matching</label></div>
		<div class="hidden-md hidden-lg col-xs-12 vspacer-lg"><label for="">Matching</label></div>
		
				
		<div class="col-xs-10 col-sm-11 col-md-8 col-lg-6">
			<div class="radio">
				<label><input type="radio" name="FilterValue[10]" class="filter_8" onclick="checkboxFilter(this)" value="1" > Checked</label>
			</div>
			<div class="radio">
				<label><input type="radio" name="FilterValue[10]" class="filter_8" onclick="checkboxFilter(this)" value="null"> Unchecked</label>
			</div>
			<div class="radio">
				<label><input type="radio" name="FilterValue[10]" class="filter_8" onclick="checkboxFilter(this)" value="" checked> Any</label>
			</div>
		</div>
		<input type="hidden" name="FilterAnd[10]" value="and">
		<input type="hidden" class='checkboxData' name="FilterField[10]" value="8">   
		<input type="hidden" name="FilterOperator[10]" id="filter_8" value="equal-to">

		
		<div class="col-xs-3 col-xs-offset-9 col-md-offset-0 col-md-1">
			<button type="button" class="btn btn-default vspacer-md btn-block" title='Clear fields' onclick="clearFilters($j(this).parent());" ><span class="glyphicon glyphicon-trash text-danger"></button>
		</div>

			</div>

	<script>
		//for population
		var filterValue_8 = '<?php echo htmlspecialchars($FilterValue[10]); ?>';
		$j(function () {
			if (filterValue_8) {
				$j("input[class =filter_8][value =" + filterValue_8 + "]").prop("checked", true).click();
			}
		})
	</script>
		
			<!-- ########################################################## -->
			
	<div class="row" style="border-bottom: dotted 2px #DDD;">
		
		<div class="hidden-xs hidden-sm col-md-3 vspacer-lg text-right"><label for="">Anonymous</label></div>
		<div class="hidden-md hidden-lg col-xs-12 vspacer-lg"><label for="">Anonymous</label></div>
		
				
		<div class="col-xs-10 col-sm-11 col-md-8 col-lg-6">
			<div class="radio">
				<label><input type="radio" name="FilterValue[11]" class="filter_9" onclick="checkboxFilter(this)" value="1" > Checked</label>
			</div>
			<div class="radio">
				<label><input type="radio" name="FilterValue[11]" class="filter_9" onclick="checkboxFilter(this)" value="null"> Unchecked</label>
			</div>
			<div class="radio">
				<label><input type="radio" name="FilterValue[11]" class="filter_9" onclick="checkboxFilter(this)" value="" checked> Any</label>
			</div>
		</div>
		<input type="hidden" name="FilterAnd[11]" value="and">
		<input type="hidden" class='checkboxData' name="FilterField[11]" value="9">   
		<input type="hidden" name="FilterOperator[11]" id="filter_9" value="equal-to">

		
		<div class="col-xs-3 col-xs-offset-9 col-md-offset-0 col-md-1">
			<button type="button" class="btn btn-default vspacer-md btn-block" title='Clear fields' onclick="clearFilters($j(this).parent());" ><span class="glyphicon glyphicon-trash text-danger"></button>
		</div>

			</div>

	<script>
		//for population
		var filterValue_9 = '<?php echo htmlspecialchars($FilterValue[11]); ?>';
		$j(function () {
			if (filterValue_9) {
				$j("input[class =filter_9][value =" + filterValue_9 + "]").prop("checked", true).click();
			}
		})
	</script>
		
			<!-- ########################################################## -->
			
	<div class="row" style="border-bottom: dotted 2px #DDD;">
		
		<div class="hidden-xs hidden-sm col-md-3 vspacer-lg text-right"><label for="">Acknowledged</label></div>
		<div class="hidden-md hidden-lg col-xs-12 vspacer-lg"><label for="">Acknowledged</label></div>
		
				
		<div class="col-xs-10 col-sm-11 col-md-8 col-lg-6">
			<div class="radio">
				<label><input type="radio" name="FilterValue[12]" class="filter_10" onclick="checkboxFilter(this)" value="1" > Checked</label>
			</div>
			<div class="radio">
				<label><input type="radio" name="FilterValue[12]" class="filter_10" onclick="checkboxFilter(this)" value="null"> Unchecked</label>
			</div>
			<div class="radio">
				<label><input type="radio" name="FilterValue[12]" class="filter_10" onclick="checkboxFilter(this)" value="" checked> Any</label>
			</div>
		</div>
		<input type="hidden" name="FilterAnd[12]" value="and">
		<input type="hidden" class='checkboxData' name="FilterField[12]" value="10">   
		<input type="hidden" name="FilterOperator[12]" id="filter_10" value="equal-to">

		
		<div class="col-xs-3 col-xs-offset-9 col-md-offset-0 col-md-1">
			<button type="button" class="btn btn-default vspacer-md btn-block" title='Clear fields' onclick="clearFilters($j(this).parent());" ><span class="glyphicon glyphicon-trash text-danger"></button>
		</div>

			</div>

	<script>
		//for population
		var filterValue_10 = '<?php echo htmlspecialchars($FilterValue[12]); ?>';
		$j(function () {
			if (filterValue_10) {
				$j("input[class =filter_10][value =" + filterValue_10 + "]").prop("checked", true).click();
			}
		})
	</script>
		
			<!-- ########################################################## -->
					
	<div class="row vspacer-lg" style="border-bottom: dotted 2px #DDD;" >
		
		<div class="hidden-xs hidden-sm col-md-3 vspacer-lg text-right"><label for="">Donation Year</label></div>
		<div class="hidden-md hidden-lg col-xs-12 vspacer-lg"><label for="">Donation Year</label></div>
		
				
		<input type="hidden" name="FilterAnd[13]" value="and">
		<input type="hidden" name="FilterField[13]" value="11">  
		<input type="hidden" name="FilterOperator[13]" value="like">
		<div class="col-md-8 col-lg-6 vspacer-md">
			<input type="text" class="form-control" name="FilterValue[13]" value="<?php echo htmlspecialchars($FilterValue[13]); ?>" size="3">
		</div>
		
		
		<div class="col-xs-3 col-xs-offset-9 col-md-offset-0 col-md-1">
			<button type="button" class="btn btn-default vspacer-md btn-block" title='Clear fields' onclick="clearFilters($j(this).parent());" ><span class="glyphicon glyphicon-trash text-danger"></button>
		</div>

			</div>


		
			<!-- ########################################################## -->
					
	<div class="row vspacer-lg" style="border-bottom: dotted 2px #DDD;" >
		
		<div class="hidden-xs hidden-sm col-md-3 vspacer-lg text-right"><label for="">In Honor Of</label></div>
		<div class="hidden-md hidden-lg col-xs-12 vspacer-lg"><label for="">In Honor Of</label></div>
		
				
		<input type="hidden" name="FilterAnd[14]" value="and">
		<input type="hidden" name="FilterField[14]" value="14">  
		<input type="hidden" name="FilterOperator[14]" value="like">
		<div class="col-md-8 col-lg-6 vspacer-md">
			<input type="text" class="form-control" name="FilterValue[14]" value="<?php echo htmlspecialchars($FilterValue[14]); ?>" size="3">
		</div>
		
		
		<div class="col-xs-3 col-xs-offset-9 col-md-offset-0 col-md-1">
			<button type="button" class="btn btn-default vspacer-md btn-block" title='Clear fields' onclick="clearFilters($j(this).parent());" ><span class="glyphicon glyphicon-trash text-danger"></button>
		</div>

			</div>


		
			<!-- ########################################################## -->
						<!-- filter actions -->
			<div class="row">
				<div class="col-md-3 col-md-offset-3 col-lg-offset-4 col-lg-2 vspacer-lg">
					<input type="hidden" name="apply_sorting" value="1">
					<button type="submit" id="applyFilters" onclick="beforeApplyFilters(event);return true;" class="btn btn-success btn-block btn-lg"><i class="glyphicon glyphicon-ok"></i> Apply filters</button>
				</div>
									<div class="col-md-3 col-lg-2 vspacer-lg">
						<button type="submit" onclick="beforeApplyFilters(event);return true;" class="btn btn-default btn-block btn-lg" id="SaveFilter" name="SaveFilter_x" value="1"><i class="glyphicon glyphicon-align-left"></i> Save &amp; apply filters</button>
					</div>
								<div class="col-md-3 col-lg-2 vspacer-lg">
					<button onclick="beforeCancelFilters();" type="submit" id="cancelFilters" class="btn btn-warning btn-block btn-lg"><i class="glyphicon glyphicon-remove"></i> Cancel</button>
				</div>
			</div>

			<script>
				$j(function(){
					//stop event if it is already bound
					$j(".numeric").off("keydown").on("keydown", function (e) {
						// Allow: backspace, delete, tab, escape, enter and .
						if ($j.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
							// Allow: Ctrl+A, Command+A
							(e.keyCode == 65 && ( e.ctrlKey === true || e.metaKey === true ) ) || 
							// Allow: home, end, left, right, down, up
							(e.keyCode >= 35 && e.keyCode <= 40)) {
								// let it happen, don't do anything
								return;
						}
						// Ensure that it is a number and stop the keypress
						if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
							e.preventDefault();
						}
					});                
				});
				
				/* function to handle the action of the clear field button */
				function clearFilters(elm){
					var parentDiv = $j(elm).parent(".row ");
					//get all input nodes
					inputValueChildren = parentDiv.find("input[type!=radio][name^=FilterValue]");
					inputRadioChildren = parentDiv.find("input[type=radio][name^=FilterValue]");
					
					//default input nodes ( text, hidden )
					inputValueChildren.each(function( index ) {
						$j( this ).val('');
					});
					
					//radio buttons
					inputRadioChildren.each(function(index) {
						$j(this).prop('checked', false);

						//checkbox case
						if($j(this).val() == '') $j(this).prop("checked", true).click();
					});
					
					//lookup and select dropdown
					parentDiv.find("div[id$=DropDown],div[id^=filter_]").select2("val", "");

					//for lookup
					parentDiv.find("input[id^=lookupoperator_]").val('equal-to');

				}
				
				function checkboxFilter(elm) {
					if (elm.value == "null") {
						$j("#" + elm.className).val("is-empty");
					} else {
						$j("#" + elm.className).val("equal-to");
					}
				}
				
				/* funtion to remove unsupplied fields */
				function beforeApplyFilters(event){
				
					// get all field submitted values
					$j(":input[type=text][name^=FilterValue],:input[type=hidden][name^=FilterValue],:input[type=radio][name^=FilterValue]:checked").each(function(index) {
						  
						// if type=hidden  and options radio fields with the same name are checked, supply its value
						if($j(this).attr('type') == 'hidden' &&  $j(":input[type=radio][name='" + $j(this).attr('name') + "']:checked").length > 0) {
							return;
						}
						  
						// do not submit fields with empty values
						if(!$j(this).val()) {
							var fieldNum = $j(this).attr('name').match(/(\d+)/)[0];
							$j(":input[name='FilterField[" + fieldNum + "]']").val('');
						};
					});

				}
				
				function beforeCancelFilters(){
					

					//other fields
					$j('form')[0].reset();

					//lookup case ( populate with initial data)
					$j(":input[class='populatedLookupData']").each(function(){
					  

						$j(":input[name='FilterValue["+$j(this).attr('name')+"]']").val($j(this).val());
						if ($j(this).val()== '<None>'){
							$j(this).parent(".row ").find('input[id^="lookupoperator"]').val('is-empty');
						}else{
							$j(this).parent(".row ").find('input[id^="lookupoperator"]').val('equal-to');
						}
							
					})

					//options case ( populate with initial data)
					$j(":input[class='populatedOptionsData']").each(function(){
					   
						$j(":input[name='FilterValue["+$j(this).attr('name')+"]']").val($j(this).val());
					})


					//checkbox, radio options case
					$j(":input[class='checkboxData'],:input[class='optionsData'] ").each(function(){
						var filterNum = $j(this).val();
						var populatedValue = eval("filterValue_"+filterNum);                  
						var parentDiv = $j(this).parent(".row ");

						//check old value
						parentDiv.find("input[type=radio][value='"+populatedValue+"']").prop('checked', true).click();
					
					})

					//remove unsuplied fields
					beforeApplyFilters();

					return true;
				}
			</script>
			
			<style>
				.form-control{ width: 100% !important; }
				.select2-container, .select2-container.vspacer-lg{ max-width: unset !important; width: 100%; margin-top: 0 !important; }
			</style>


		

<?php } ?>