<?php
	define('PREPEND_PATH', '');
	include_once(__DIR__ . '/lib.php');

	// accept a record as an assoc array, return transformed row ready to insert to table
	$transformFunctions = [
		'Supporters' => function($data, $options = []) {
			if(isset($data['Phone'])) $data['Phone'] = str_replace('-', '', $data['Phone']);
			if(isset($data['Cell'])) $data['Cell'] = str_replace('-', '', $data['Cell']);

			return $data;
		},
		'Campaigns' => function($data, $options = []) {
			if(isset($data['StartDate'])) $data['StartDate'] = guessMySQLDateTime($data['StartDate']);
			if(isset($data['EndDate'])) $data['EndDate'] = guessMySQLDateTime($data['EndDate']);
			if(isset($data['Goal'])) $data['Goal'] = preg_replace('/[^\d\.]/', '', $data['Goal']);
			if(isset($data['DateClosed'])) $data['DateClosed'] = guessMySQLDateTime($data['DateClosed']);

			return $data;
		},
		'Donations' => function($data, $options = []) {
			if(isset($data['DonationDate'])) $data['DonationDate'] = guessMySQLDateTime($data['DonationDate']);
			if(isset($data['Amount'])) $data['Amount'] = preg_replace('/[^\d\.]/', '', $data['Amount']);
			if(isset($data['SupporterID'])) $data['SupporterID'] = pkGivenLookupText($data['SupporterID'], 'Donations', 'SupporterID');
			if(isset($data['CampaignID'])) $data['CampaignID'] = pkGivenLookupText($data['CampaignID'], 'Donations', 'CampaignID');
			if(isset($data['MailingName'])) $data['MailingName'] = thisOr($data['SupporterID'], pkGivenLookupText($data['MailingName'], 'Donations', 'MailingName'));
			if(isset($data['Address1'])) $data['Address1'] = thisOr($data['SupporterID'], pkGivenLookupText($data['Address1'], 'Donations', 'Address1'));
			if(isset($data['Address2'])) $data['Address2'] = thisOr($data['SupporterID'], pkGivenLookupText($data['Address2'], 'Donations', 'Address2'));
			if(isset($data['City'])) $data['City'] = thisOr($data['SupporterID'], pkGivenLookupText($data['City'], 'Donations', 'City'));
			if(isset($data['State'])) $data['State'] = thisOr($data['SupporterID'], pkGivenLookupText($data['State'], 'Donations', 'State'));
			if(isset($data['Zip'])) $data['Zip'] = thisOr($data['SupporterID'], pkGivenLookupText($data['Zip'], 'Donations', 'Zip'));
			if(isset($data['Country'])) $data['Country'] = thisOr($data['SupporterID'], pkGivenLookupText($data['Country'], 'Donations', 'Country'));

			return $data;
		},
		'Notes' => function($data, $options = []) {
			if(isset($data['SupporterID'])) $data['SupporterID'] = pkGivenLookupText($data['SupporterID'], 'Notes', 'SupporterID');
			if(isset($data['Date'])) $data['Date'] = guessMySQLDateTime($data['Date']);

			return $data;
		},
		'MatchingFunds' => function($data, $options = []) {
			if(isset($data['Amount'])) $data['Amount'] = preg_replace('/[^\d\.]/', '', $data['Amount']);
			if(isset($data['SupporterID'])) $data['SupporterID'] = pkGivenLookupText($data['SupporterID'], 'MatchingFunds', 'SupporterID');
			if(isset($data['DateSubmitted'])) $data['DateSubmitted'] = guessMySQLDateTime($data['DateSubmitted']);
			if(isset($data['DateReceived'])) $data['DateReceived'] = guessMySQLDateTime($data['DateReceived']);
			if(isset($data['DonationID'])) $data['DonationID'] = pkGivenLookupText($data['DonationID'], 'MatchingFunds', 'DonationID');

			return $data;
		},
	];

	// accept a record as an assoc array, return a boolean indicating whether to import or skip record
	$filterFunctions = [
		'Supporters' => function($data, $options = []) { return true; },
		'Campaigns' => function($data, $options = []) { return true; },
		'Donations' => function($data, $options = []) { return true; },
		'Notes' => function($data, $options = []) { return true; },
		'MatchingFunds' => function($data, $options = []) { return true; },
	];

	/*
	Hook file for overwriting/amending $transformFunctions and $filterFunctions:
	hooks/import-csv.php
	If found, it's included below

	The way this works is by either completely overwriting any of the above 2 arrays,
	or, more commonly, overwriting a single function, for example:
		$transformFunctions['tablename'] = function($data, $options = []) {
			// new definition here
			// then you must return transformed data
			return $data;
		};

	Another scenario is transforming a specific field and leaving other fields to the default
	transformation. One possible way of doing this is to store the original transformation function
	in GLOBALS array, calling it inside the custom transformation function, then modifying the
	specific field:
		$GLOBALS['originalTransformationFunction'] = $transformFunctions['tablename'];
		$transformFunctions['tablename'] = function($data, $options = []) {
			$data = call_user_func_array($GLOBALS['originalTransformationFunction'], [$data, $options]);
			$data['fieldname'] = 'transformed value';
			return $data;
		};
	*/

	@include(__DIR__ . '/hooks/import-csv.php');

	$ui = new CSVImportUI($transformFunctions, $filterFunctions);
