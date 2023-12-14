<?php
	// check this file's MD5 to make sure it wasn't called before
	$tenantId = Authentication::tenantIdPadded();
	$setupHash = __DIR__ . "/setup{$tenantId}.md5";

	$prevMD5 = @file_get_contents($setupHash);
	$thisMD5 = md5_file(__FILE__);

	// check if this setup file already run
	if($thisMD5 != $prevMD5) {
		// set up tables
		setupTable(
			'Supporters', " 
			CREATE TABLE IF NOT EXISTS `Supporters` ( 
				`ID` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
				PRIMARY KEY (`ID`),
				`FirstName` VARCHAR(40) NULL,
				`LastName` VARCHAR(50) NULL,
				`SpouseName` VARCHAR(40) NULL,
				`Address1` VARCHAR(255) NULL,
				`Address2` VARCHAR(255) NULL,
				`City` VARCHAR(85) NULL,
				`State` CHAR(2) NULL,
				`Zip` VARCHAR(10) NULL,
				`Country` VARCHAR(40) NULL DEFAULT 'United States',
				`Phone` VARCHAR(20) NULL,
				`Cell` VARCHAR(20) NULL,
				`Email` VARCHAR(80) NULL,
				`Status` VARCHAR(10) NULL DEFAULT 'Active',
				`ContactMethod` TINYTEXT NULL,
				`TotalDonated` DECIMAL(11,2) NULL,
				`TotalMatched` DECIMAL(11,2) NULL,
				`MailingName` VARCHAR(40) NULL,
				`MailingNameFull` VARCHAR(90) NULL
			) CHARSET utf8"
		);

		setupTable(
			'Campaigns', " 
			CREATE TABLE IF NOT EXISTS `Campaigns` ( 
				`ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
				PRIMARY KEY (`ID`),
				`CampaignName` VARCHAR(40) NULL,
				`StartDate` DATETIME NULL,
				`EndDate` DATETIME NULL,
				`Goal` DECIMAL(10,2) NULL,
				`DateClosed` DATETIME NULL,
				`Status` VARCHAR(10) NULL
			) CHARSET utf8"
		);

		setupTable(
			'Donations', " 
			CREATE TABLE IF NOT EXISTS `Donations` ( 
				`ID` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
				PRIMARY KEY (`ID`),
				`DonationDate` DATE NULL,
				`Amount` DECIMAL(10,2) NOT NULL,
				`Description` TEXT NULL,
				`SupporterID` INT(11) UNSIGNED NULL,
				`CampaignID` INT UNSIGNED NULL,
				`Paytype` TEXT NOT NULL,
				`Matching` INT NULL,
				`Anonymous` INT NULL,
				`Acknowledged` INT NULL,
				`DonationYear` VARCHAR(4) NULL,
				`Notes` TEXT NULL,
				`MemoryOf` VARCHAR(40) NULL,
				`HonorOf` VARCHAR(40) NULL,
				`MailingName` INT(11) UNSIGNED NULL,
				`Address1` INT(11) UNSIGNED NULL,
				`Address2` INT(11) UNSIGNED NULL,
				`City` INT(11) UNSIGNED NULL,
				`State` INT(11) UNSIGNED NULL,
				`Zip` INT(11) UNSIGNED NULL,
				`Country` INT(11) UNSIGNED NULL
			) CHARSET utf8"
		);
		setupIndexes('Donations', ['SupporterID','CampaignID',]);

		setupTable(
			'Notes', " 
			CREATE TABLE IF NOT EXISTS `Notes` ( 
				`ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
				PRIMARY KEY (`ID`),
				`SupporterID` INT(11) UNSIGNED NULL,
				`Date` DATETIME NULL,
				`Note` TEXT NULL
			) CHARSET utf8"
		);
		setupIndexes('Notes', ['SupporterID',]);

		setupTable(
			'MatchingFunds', " 
			CREATE TABLE IF NOT EXISTS `MatchingFunds` ( 
				`ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
				PRIMARY KEY (`ID`),
				`OrganizationName` VARCHAR(40) NULL,
				`Amount` DECIMAL(10,2) NULL,
				`SupporterID` INT(11) UNSIGNED NULL,
				`DateSubmitted` DATETIME NULL,
				`DateReceived` DATETIME NULL,
				`Notes` TEXT NULL,
				`DonationID` INT(11) UNSIGNED NULL,
				`Attachment` BLOB NULL
			) CHARSET utf8"
		);
		setupIndexes('MatchingFunds', ['SupporterID','DonationID',]);



		// save MD5
		@file_put_contents($setupHash, $thisMD5);
	}


	function setupIndexes($tableName, $arrFields) {
		if(!is_array($arrFields) || !count($arrFields)) return false;

		foreach($arrFields as $fieldName) {
			if(!$res = @db_query("SHOW COLUMNS FROM `$tableName` like '$fieldName'")) continue;
			if(!$row = @db_fetch_assoc($res)) continue;
			if($row['Key']) continue;

			@db_query("ALTER TABLE `$tableName` ADD INDEX `$fieldName` (`$fieldName`)");
		}
	}


	function setupTable($tableName, $createSQL = '', $arrAlter = '') {
		global $Translation;
		$oldTableName = '';
		ob_start();

		echo '<div style="padding: 5px; border-bottom:solid 1px silver; font-family: verdana, arial; font-size: 10px;">';

		// is there a table rename query?
		if(is_array($arrAlter)) {
			$matches = [];
			if(preg_match("/ALTER TABLE `(.*)` RENAME `$tableName`/i", $arrAlter[0], $matches)) {
				$oldTableName = $matches[1];
			}
		}

		if($res = @db_query("SELECT COUNT(1) FROM `$tableName`")) { // table already exists
			if($row = @db_fetch_array($res)) {
				echo str_replace(['<TableName>', '<NumRecords>'], [$tableName, $row[0]], $Translation['table exists']);
				if(is_array($arrAlter)) {
					echo '<br>';
					foreach($arrAlter as $alter) {
						if($alter != '') {
							echo "$alter ... ";
							if(!@db_query($alter)) {
								echo '<span class="label label-danger">' . $Translation['failed'] . '</span>';
								echo '<div class="text-danger">' . $Translation['mysql said'] . ' ' . db_error(db_link()) . '</div>';
							} else {
								echo '<span class="label label-success">' . $Translation['ok'] . '</span>';
							}
						}
					}
				} else {
					echo $Translation['table uptodate'];
				}
			} else {
				echo str_replace('<TableName>', $tableName, $Translation['couldnt count']);
			}
		} else { // given tableName doesn't exist

			if($oldTableName != '') { // if we have a table rename query
				if($ro = @db_query("SELECT COUNT(1) FROM `$oldTableName`")) { // if old table exists, rename it.
					$renameQuery = array_shift($arrAlter); // get and remove rename query

					echo "$renameQuery ... ";
					if(!@db_query($renameQuery)) {
						echo '<span class="label label-danger">' . $Translation['failed'] . '</span>';
						echo '<div class="text-danger">' . $Translation['mysql said'] . ' ' . db_error(db_link()) . '</div>';
					} else {
						echo '<span class="label label-success">' . $Translation['ok'] . '</span>';
					}

					if(is_array($arrAlter)) setupTable($tableName, $createSQL, false, $arrAlter); // execute Alter queries on renamed table ...
				} else { // if old tableName doesn't exist (nor the new one since we're here), then just create the table.
					setupTable($tableName, $createSQL, false); // no Alter queries passed ...
				}
			} else { // tableName doesn't exist and no rename, so just create the table
				echo str_replace("<TableName>", $tableName, $Translation["creating table"]);
				if(!@db_query($createSQL)) {
					echo '<span class="label label-danger">' . $Translation['failed'] . '</span>';
					echo '<div class="text-danger">' . $Translation['mysql said'] . db_error(db_link()) . '</div>';

					// create table with a dummy field
					@db_query("CREATE TABLE IF NOT EXISTS `$tableName` (`_dummy_deletable_field` TINYINT)");
				} else {
					echo '<span class="label label-success">' . $Translation['ok'] . '</span>';
				}
			}

			// set Admin group permissions for newly created table if membership_grouppermissions exists
			if($ro = @db_query("SELECT COUNT(1) FROM `membership_grouppermissions`")) {
				// get Admins group id
				$ro = @db_query("SELECT `groupID` FROM `membership_groups` WHERE `name`='Admins'");
				if($ro) {
					$adminGroupID = intval(db_fetch_row($ro)[0]);
					if($adminGroupID) @db_query("INSERT IGNORE INTO `membership_grouppermissions` SET
						`groupID`='$adminGroupID',
						`tableName`='$tableName',
						`allowInsert`=1, `allowView`=1, `allowEdit`=1, `allowDelete`=1
					");
				}
			}
		}

		echo '</div>';

		$out = ob_get_clean();
		if(defined('APPGINI_SETUP') && APPGINI_SETUP) echo $out;
	}
