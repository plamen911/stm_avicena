<?php

ini_set('memory_limit', '64M');
set_time_limit(600);

require ("config.php");
require ("functions.php");
require ("sqlitedb.php");
require ("convertroman.php");

$_DBServer = 'localhost';
$_DBUser = 'root';
$_DBPass = 'Mitkov4069';
$_DBName = 'stm_bcb';

$link = mysql_connect($_DBServer, $_DBUser, $_DBPass);
if (!$link) {
	echo "Can't connect to server: ".mysql_error();	// can't connect to server
	exit();
}

$db_selected = mysql_select_db($_DBName, $link);
if (!$db_selected) {
	mysql_query("SET SQL_MODE=\"NO_AUTO_VALUE_ON_ZERO\"");
	mysql_query("DROP DATABASE `$_DBName`");
	mysql_query("CREATE DATABASE `$_DBName` DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
}

$db_selected = mysql_select_db($_DBName, $link);
if (!$db_selected) {
	echo "Can't connect to database: ".mysql_error();	// can't connect to database
	exit();
}

mysql_query("SET NAMES 'utf8' COLLATE 'utf8_unicode_ci'");

$dbInst = new SqliteDB();

// tables to import
$tables = array('anamnesis', 'cfg_doctor_positions', 'chart_types', 'communities', 'doctors', 'family_diseases', 'family_weights', 'firms', 'firm_positions', 'firm_struct_map', 'lab_checkups', 'lab_indicators', 'locations', 'medical_checkups', 'medical_checkups_doctors', 'medical_reasons', 'mkb', 'mkb_classes', 'mkb_groups', 'patient_charts', 'prchk_diagnosis', 'provinces', 'pro_route', 'stm_info', 'subdivisions', 'telks', 'users', 'workers', 'work_env_protocols', 'work_places', 'wplace_factors_map', 'wplace_prot_map');

create_tables($tables);

foreach ($tables as $table) {
	$rows = $dbInst->query("SELECT * FROM `$table`");
	$i = 0;
	if(!empty($rows)) {
		//mysql_query("TRUNCATE TABLE `$table`");
		foreach ($rows as $num => $arr1) {
			$cols = $fields = array();
			foreach ($arr1 as $key => $value) {
				if(is_numeric($key)) continue;
				if('mkb' == $table && 'mkb_id' == $key && empty($value)) continue;

				$cols[] = $key;
				$fields[] = addslashes(stripslashes($value));
			}
			$sql = "INSERT INTO `$table` (`" . implode("`,`", $cols) . "`) VALUES ('" . implode("','", $fields) . "')";
			mysql_query($sql) or die($sql.' / '.mysql_error());
			$i++;
		}

	}
	echo 'Nice work! '.$i.' records inserted into `'.$table.'` table.<br />';
}

if(in_array('workers', $tables) && in_array('firms', $tables)) {
	$result = mysql_query("SELECT * FROM `firms`");
	if(mysql_num_rows($result)) {
		while ($row = mysql_fetch_assoc($result)) {
			$sql = "SELECT COUNT(*) AS `cnt` FROM `workers` WHERE `firm_id` = $row[firm_id] AND ( `date_retired` = '' OR `date_retired` = '0000-00-00 00:00:00' ) AND `is_active` = '1'";
			$rs = mysql_query($sql);
			$f = mysql_fetch_assoc($rs);
			$num_workers = (!empty($f['cnt'])) ? $f['cnt'] : 0;
			$sql = "UPDATE `firms` SET `num_workers` = '$num_workers' WHERE `firm_id` = $row[firm_id]";
			mysql_query($sql) or die(mysql_error());
		}
	}
}

function create_tables($tables) {

	if(in_array('anamnesis', $tables)) {
		$sql = "DROP TABLE IF EXISTS `anamnesis`";
		mysql_query($sql) or die(mysql_error());
		$sql = "CREATE TABLE IF NOT EXISTS `anamnesis` (
			  `anamnesis_id` int(11) NOT NULL auto_increment,
			  `firm_id` int(11) default '0',
			  `worker_id` int(11) default '0',
			  `checkup_id` int(11) default '0',
			  `mkb_id` varchar(10) collate utf8_unicode_ci default NULL,
			  `diagnosis` text collate utf8_unicode_ci,
			  PRIMARY KEY  (`anamnesis_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		mysql_query($sql) or die(mysql_error());
	}
	//
	if(in_array('cfg_doctor_positions', $tables)) {
		$sql = "DROP TABLE IF EXISTS `cfg_doctor_positions`";
		mysql_query($sql) or die(mysql_error());
		$sql = "CREATE TABLE IF NOT EXISTS `cfg_doctor_positions` (
			  `doctor_pos_id` int(11) NOT NULL auto_increment,
			  `doctor_pos_name` varchar(255) collate utf8_unicode_ci default NULL,
			  PRIMARY KEY  (`doctor_pos_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		mysql_query($sql) or die(mysql_error());
	}
	//
	if(in_array('chart_types', $tables)) {
		$sql = "DROP TABLE IF EXISTS `chart_types`";
		mysql_query($sql) or die(mysql_error());
		$sql = "CREATE TABLE IF NOT EXISTS `chart_types` (
			  `type_id` int(11) NOT NULL auto_increment,
			  `type_desc` varchar(40) collate utf8_unicode_ci default NULL,
			  `type_desc_short` varchar(40) collate utf8_unicode_ci default NULL,
			  PRIMARY KEY  (`type_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		mysql_query($sql) or die(mysql_error());
	}
	//
	if(in_array('communities', $tables)) {
		$sql = "DROP TABLE IF EXISTS `communities`";
		mysql_query($sql) or die(mysql_error());
		$sql = "CREATE TABLE IF NOT EXISTS `communities` (
			  `community_id` int(11) NOT NULL auto_increment,
			  `province_id` int(11) default '0',
			  `community_name` varchar(60) collate utf8_unicode_ci default NULL,
			  `community_type` char(1) collate utf8_unicode_ci default '1',
			  PRIMARY KEY  (`community_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		mysql_query($sql) or die(mysql_error());
	}
	//
	if(in_array('doctors', $tables)) {
		$sql = "DROP TABLE IF EXISTS `doctors`";
		mysql_query($sql) or die(mysql_error());
		$sql = "CREATE TABLE IF NOT EXISTS `doctors` (
			  `doctor_id` int(11) NOT NULL auto_increment,
			  `doctor_name` varchar(255) collate utf8_unicode_ci default NULL,
			  `address` varchar(255) collate utf8_unicode_ci default NULL,
			  `phone1` varchar(60) collate utf8_unicode_ci default NULL,
			  `phone2` varchar(60) collate utf8_unicode_ci default NULL,
			  PRIMARY KEY  (`doctor_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		mysql_query($sql) or die(mysql_error());
	}
	//
	if(in_array('family_diseases', $tables)) {
		$sql = "DROP TABLE IF EXISTS `family_diseases`";
		mysql_query($sql) or die(mysql_error());
		$sql = "CREATE TABLE IF NOT EXISTS `family_diseases` (
			  `disease_id` int(11) NOT NULL auto_increment,
			  `firm_id` int(11) default '0',
			  `worker_id` int(11) default '0',
			  `checkup_id` int(11) default '0',
			  `mkb_id` varchar(10) collate utf8_unicode_ci default NULL,
			  `diagnosis` text collate utf8_unicode_ci,
			  `is_new` char(1) collate utf8_unicode_ci default '0',
			  PRIMARY KEY  (`disease_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		mysql_query($sql) or die(mysql_error());
	}
	//
	if(in_array('family_weights', $tables)) {
		$sql = "DROP TABLE IF EXISTS `family_weights`";
		mysql_query($sql) or die(mysql_error());
		$sql = "CREATE TABLE IF NOT EXISTS `family_weights` (
			  `family_weight_id` int(11) NOT NULL auto_increment,
			  `firm_id` int(11) default '0',
			  `worker_id` int(11) default '0',
			  `checkup_id` int(11) default '0',
			  `mkb_id` varchar(10) collate utf8_unicode_ci default NULL,
			  `diagnosis` text collate utf8_unicode_ci,
			  PRIMARY KEY  (`family_weight_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		mysql_query($sql) or die(mysql_error());
	}
	//
	if(in_array('firms', $tables)) {
		$sql = "DROP TABLE IF EXISTS `firms`";
		mysql_query($sql) or die(mysql_error());
		$sql = "CREATE TABLE IF NOT EXISTS `firms` (
			  `firm_id` int(11) NOT NULL auto_increment,
			  `is_active` char(1) collate utf8_unicode_ci default '1',
			  `name` varchar(60) collate utf8_unicode_ci default NULL,
			  `location_id` int(11) default '0',
			  `community_id` int(11) default '0',
			  `province_id` int(11) default '0',
			  `address` varchar(100) collate utf8_unicode_ci default NULL,
			  `email` varchar(40) collate utf8_unicode_ci default NULL,
			  `notes` text collate utf8_unicode_ci,
			  `phone1` tinytext collate utf8_unicode_ci,
			  `phone2` tinytext collate utf8_unicode_ci,
			  `fax` tinytext collate utf8_unicode_ci,
			  `date_added` datetime default NULL,
			  `date_modified` datetime default NULL,
			  `modified_by` int(11) default '1',
			  `contract_num` varchar(40) collate utf8_unicode_ci default NULL,
			  `contract_begin` datetime default NULL,
			  `contract_end` datetime default NULL,
			  `firm_folder` varchar(100) collate utf8_unicode_ci default NULL,
			  `bulstat` varchar(100) collate utf8_unicode_ci default NULL,
			  `num_workers` int(11) unsigned NOT NULL default '0',
			  PRIMARY KEY  (`firm_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		mysql_query($sql) or die(mysql_error());
	}
	//
	if(in_array('firm_positions', $tables)) {
		$sql = "DROP TABLE IF EXISTS `firm_positions`";
		mysql_query($sql) or die(mysql_error());
		$sql = "CREATE TABLE IF NOT EXISTS `firm_positions` (
			  `position_id` int(11) NOT NULL auto_increment,
			  `firm_id` int(11) default '0',
			  `position_name` varchar(100) collate utf8_unicode_ci default NULL,
			  `position_workcond` text collate utf8_unicode_ci,
			  `position_position` int(11) default '1',
			  PRIMARY KEY  (`position_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		mysql_query($sql) or die(mysql_error());
	}
	//
	if(in_array('firm_struct_map', $tables)) {
		$sql = "DROP TABLE IF EXISTS `firm_struct_map`";
		mysql_query($sql) or die(mysql_error());
		$sql = "CREATE TABLE IF NOT EXISTS `firm_struct_map` (
			  `map_id` int(11) NOT NULL auto_increment,
			  `firm_id` int(11) default '0',
			  `subdivision_id` int(11) default '0',
			  `spos` int(11) default '1',
			  `wplace_id` int(11) default '0',
			  `wpos` int(11) default '1',
			  `position_id` int(11) default '0',
			  `ppos` int(11) default '1',
			  PRIMARY KEY  (`map_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		mysql_query($sql) or die(mysql_error());
	}
	//
	if(in_array('lab_checkups', $tables)) {
		$sql = "DROP TABLE IF EXISTS `lab_checkups`";
		mysql_query($sql) or die(mysql_error());
		$sql = "CREATE TABLE IF NOT EXISTS `lab_checkups` (
			  `lab_checkup_id` int(11) NOT NULL auto_increment,
			  `firm_id` int(11) default '0',
			  `worker_id` int(11) default '0',
			  `checkup_id` int(11) default '0',
			  `indicator_id` int(11) default '0',
			  `checkup_type` varchar(40) collate utf8_unicode_ci default NULL,
			  `checkup_level` float default NULL,
			  PRIMARY KEY  (`lab_checkup_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		mysql_query($sql) or die(mysql_error());
	}
	//
	if(in_array('lab_indicators', $tables)) {
		$sql = "DROP TABLE IF EXISTS `lab_indicators`";
		mysql_query($sql) or die(mysql_error());
		$sql = "CREATE TABLE IF NOT EXISTS `lab_indicators` (
			  `indicator_id` int(11) NOT NULL auto_increment,
			  `indicator_type` varchar(60) collate utf8_unicode_ci default NULL,
			  `indicator_name` varchar(60) collate utf8_unicode_ci default NULL,
			  `pdk_min` varchar(20) collate utf8_unicode_ci default NULL,
			  `pdk_max` varchar(20) collate utf8_unicode_ci default NULL,
			  `indicator_dimension` varchar(20) collate utf8_unicode_ci default NULL,
			  `indicator_position` int(11) default '1',
			  PRIMARY KEY  (`indicator_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		mysql_query($sql) or die(mysql_error());
	}
	//
	if(in_array('locations', $tables)) {
		$sql = "DROP TABLE IF EXISTS `locations`";
		mysql_query($sql) or die(mysql_error());
		$sql = "CREATE TABLE IF NOT EXISTS `locations` (
			  `location_id` int(11) NOT NULL auto_increment,
			  `community_id` int(11) default '0',
			  `location_name` varchar(60) collate utf8_unicode_ci default NULL,
			  `location_type` char(1) collate utf8_unicode_ci default '0',
			  PRIMARY KEY  (`location_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		mysql_query($sql) or die(mysql_error());
	}
	//
	if(in_array('medical_checkups', $tables)) {
		$sql = "DROP TABLE IF EXISTS `medical_checkups`";
		mysql_query($sql) or die(mysql_error());
		$sql = "CREATE TABLE IF NOT EXISTS `medical_checkups` (
			  `checkup_id` int(11) NOT NULL auto_increment,
			  `firm_id` int(11) default '0',
			  `worker_id` int(11) default '0',
			  `year_to_be_done` int(11) default '0',
			  `checkup_date` datetime default NULL,
			  `hospital` varchar(100) collate utf8_unicode_ci default NULL,
			  `worker_height` float default '0',
			  `worker_weight` float default '0',
			  `rr_syst` int(11) default NULL,
			  `rr_diast` int(11) default NULL,
			  `hours_activity` float default '0',
			  `home_stress` char(1) collate utf8_unicode_ci default '0',
			  `work_stress` char(1) collate utf8_unicode_ci default '0',
			  `social_stress` char(1) collate utf8_unicode_ci default '0',
			  `video_display` char(1) collate utf8_unicode_ci default '0',
			  `smoking` char(1) collate utf8_unicode_ci default '0',
			  `drinking` char(1) collate utf8_unicode_ci default '0',
			  `fats` char(1) collate utf8_unicode_ci default '0',
			  `diet` char(1) collate utf8_unicode_ci default '0',
			  `left_eye` float default NULL,
			  `left_eye2` float default '1',
			  `right_eye` float default NULL,
			  `right_eye2` float default '1',
			  `VK` float default NULL,
			  `FEO1` float default NULL,
			  `tifno` varchar(40) collate utf8_unicode_ci default NULL,
			  `hearing_loss` varchar(40) collate utf8_unicode_ci default NULL,
			  `hearing_diagnose` text collate utf8_unicode_ci,
			  `left_ear` float default NULL,
			  `right_ear` float default NULL,
			  `EKG` text collate utf8_unicode_ci,
			  `x_ray` text collate utf8_unicode_ci,
			  `echo_ray` text collate utf8_unicode_ci,
			  `desc_GP` text collate utf8_unicode_ci,
			  `desc_pathologist` text collate utf8_unicode_ci,
			  `desc_neurologist` text collate utf8_unicode_ci,
			  `desc_UNG` text collate utf8_unicode_ci,
			  `desc_ophthalmologist` text collate utf8_unicode_ci,
			  `desc_dermatologist` text collate utf8_unicode_ci,
			  `desc_surgeon` text collate utf8_unicode_ci,
			  `conclusion` varchar(60) collate utf8_unicode_ci default NULL,
			  `notes` text collate utf8_unicode_ci,
			  `stm_conclusion` varchar(60) collate utf8_unicode_ci default NULL,
			  `stm_conditions` text collate utf8_unicode_ci,
			  `stm_date` datetime default NULL,
			  `date_added` datetime default NULL,
			  `date_modified` datetime default NULL,
			  PRIMARY KEY  (`checkup_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		mysql_query($sql) or die(mysql_error());
	}
	//
	if(in_array('medical_checkups_doctors', $tables)) {
		$sql = "DROP TABLE IF EXISTS `medical_checkups_doctors`";
		mysql_query($sql) or die(mysql_error());
		$sql = "CREATE TABLE IF NOT EXISTS `medical_checkups_doctors` (
			  `checkup_id` int(11) NOT NULL default '0',
			  `doctor_pos_id` int(11) default '0',
			  `doctor_desc` text collate utf8_unicode_ci,
			  `position` int(11) default '1'
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		mysql_query($sql) or die(mysql_error());
	}
	//
	if(in_array('medical_reasons', $tables)) {
		$sql = "DROP TABLE IF EXISTS `medical_reasons`";
		mysql_query($sql) or die(mysql_error());
		$sql = "CREATE TABLE IF NOT EXISTS `medical_reasons` (
			  `reason_id` varchar(10) collate utf8_unicode_ci NOT NULL,
			  `reason_desc` varchar(255) collate utf8_unicode_ci default NULL,
			  PRIMARY KEY  (`reason_id`),
			  UNIQUE KEY `reason_id` (`reason_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		mysql_query($sql) or die(mysql_error());
	}
	//
	if(in_array('mkb', $tables)) {
		$sql = "DROP TABLE IF EXISTS `mkb`";
		mysql_query($sql) or die(mysql_error());
		$sql = "CREATE TABLE IF NOT EXISTS `mkb` (
			  `mkb_id` varchar(10) collate utf8_unicode_ci NOT NULL,
			  `group_id` int(11) default '0',
			  `mkb_desc` varchar(255) collate utf8_unicode_ci default NULL,
			  `mkb_code` varchar(10) collate utf8_unicode_ci default NULL,
			  PRIMARY KEY  (`mkb_id`),
			  UNIQUE KEY `mkb_id` (`mkb_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		mysql_query($sql) or die(mysql_error());
	}
	//
	if(in_array('mkb_classes', $tables)) {
		$sql = "DROP TABLE IF EXISTS `mkb_classes`";
		mysql_query($sql) or die(mysql_error());
		$sql = "CREATE TABLE IF NOT EXISTS `mkb_classes` (
			  `class_id` int(11) NOT NULL auto_increment,
			  `class_name` varchar(255) collate utf8_unicode_ci default NULL,
			  PRIMARY KEY  (`class_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		mysql_query($sql) or die(mysql_error());
	}
	//
	if(in_array('mkb_groups', $tables)) {
		$sql = "DROP TABLE IF EXISTS `mkb_groups`";
		mysql_query($sql) or die(mysql_error());
		$sql = "CREATE TABLE IF NOT EXISTS `mkb_groups` (
			  `group_id` int(11) NOT NULL auto_increment,
			  `class_id` int(11) default '0',
			  `group_name` varchar(255) collate utf8_unicode_ci default NULL,
			  PRIMARY KEY  (`group_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		mysql_query($sql) or die(mysql_error());
	}
	//
	if(in_array('patient_charts', $tables)) {
		$sql = "DROP TABLE IF EXISTS `patient_charts`";
		mysql_query($sql) or die(mysql_error());
		$sql = "CREATE TABLE IF NOT EXISTS `patient_charts` (
			  `chart_id` int(11) NOT NULL auto_increment,
			  `firm_id` int(11) default '0',
			  `worker_id` int(11) default '0',
			  `chart_num` varchar(40) collate utf8_unicode_ci default NULL,
			  `hospital_date_from` datetime default NULL,
			  `hospital_date_to` datetime default NULL,
			  `days_off` int(11) default '0',
			  `mkb_id` varchar(10) collate utf8_unicode_ci default NULL,
			  `medical_types` varchar(40) collate utf8_unicode_ci default NULL,
			  `reason_id` varchar(10) collate utf8_unicode_ci default NULL,
			  `chart_desc` text collate utf8_unicode_ci,
			  `date_added` datetime default NULL,
			  `date_modified` datetime default NULL,
			  PRIMARY KEY  (`chart_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		mysql_query($sql) or die(mysql_error());
	}
	//
	if(in_array('prchk_diagnosis', $tables)) {
		$sql = "DROP TABLE IF EXISTS `prchk_diagnosis`";
		mysql_query($sql) or die(mysql_error());
		$sql = "CREATE TABLE IF NOT EXISTS `prchk_diagnosis` (
			  `prchk_id` int(11) NOT NULL auto_increment,
			  `worker_id` int(11) default '0',
			  `mkb_id` varchar(10) collate utf8_unicode_ci default NULL,
			  `diagnosis` text collate utf8_unicode_ci,
			  `published_by` varchar(40) collate utf8_unicode_ci default NULL,
			  PRIMARY KEY  (`prchk_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		mysql_query($sql) or die(mysql_error());
	}
	//
	if(in_array('provinces', $tables)) {
		$sql = "DROP TABLE IF EXISTS `provinces`";
		mysql_query($sql) or die(mysql_error());
		$sql = "CREATE TABLE IF NOT EXISTS `provinces` (
			  `province_id` int(11) NOT NULL auto_increment,
			  `province_name` varchar(60) collate utf8_unicode_ci default NULL,
			  `province_type` char(1) collate utf8_unicode_ci default '1',
			  PRIMARY KEY  (`province_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		mysql_query($sql) or die(mysql_error());
	}
	//
	if(in_array('pro_route', $tables)) {
		$sql = "DROP TABLE IF EXISTS `pro_route`";
		mysql_query($sql) or die(mysql_error());
		$sql = "CREATE TABLE IF NOT EXISTS `pro_route` (
			  `route_id` int(11) NOT NULL auto_increment,
			  `worker_id` int(11) default '0',
			  `firm_name` varchar(80) collate utf8_unicode_ci default NULL,
			  `position` varchar(80) collate utf8_unicode_ci default NULL,
			  `exp_length_y` int(11) default '0',
			  `exp_length_m` int(11) default '0',
			  PRIMARY KEY  (`route_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		mysql_query($sql) or die(mysql_error());
	}
	//
	if(in_array('stm_info', $tables)) {
		$sql = "DROP TABLE IF EXISTS `stm_info`";
		mysql_query($sql) or die(mysql_error());
		$sql = "CREATE TABLE IF NOT EXISTS `stm_info` (
			  `stm_id` int(11) NOT NULL auto_increment,
			  `stm_name` varchar(100) collate utf8_unicode_ci default NULL,
			  `stm_info` varchar(100) collate utf8_unicode_ci default NULL,
			  `license_num` varchar(60) collate utf8_unicode_ci default NULL,
			  `address` varchar(100) collate utf8_unicode_ci default NULL,
			  `chief` varchar(100) collate utf8_unicode_ci default NULL,
			  `phone1` varchar(40) collate utf8_unicode_ci default NULL,
			  `phone2` varchar(40) collate utf8_unicode_ci default NULL,
			  `fax` varchar(40) collate utf8_unicode_ci default NULL,
			  `email` varchar(40) collate utf8_unicode_ci default NULL,
			  `contract_exp_days` int(11) default '7',
			  PRIMARY KEY  (`stm_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		mysql_query($sql) or die(mysql_error());
	}
	//
	if(in_array('subdivisions', $tables)) {
		$sql = "DROP TABLE IF EXISTS `subdivisions`";
		mysql_query($sql) or die(mysql_error());
		$sql = "CREATE TABLE IF NOT EXISTS `subdivisions` (
			  `subdivision_id` int(11) NOT NULL auto_increment,
			  `firm_id` int(11) default '0',
			  `subdivision_name` varchar(100) collate utf8_unicode_ci default NULL,
			  `subdivision_position` int(11) default '1',
			  PRIMARY KEY  (`subdivision_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		mysql_query($sql) or die(mysql_error());
	}
	//
	if(in_array('telks', $tables)) {
		$sql = "DROP TABLE IF EXISTS `telks`";
		mysql_query($sql) or die(mysql_error());
		$sql = "CREATE TABLE IF NOT EXISTS `telks` (
			  `telk_id` int(11) NOT NULL auto_increment,
			  `firm_id` int(11) default '0',
			  `worker_id` int(11) default '0',
			  `telk_num` char(4) collate utf8_unicode_ci default NULL,
			  `telk_date_from` datetime default NULL,
			  `telk_date_to` datetime default NULL,
			  `telk_duration` varchar(40) collate utf8_unicode_ci default NULL,
			  `mkb_id_1` varchar(10) collate utf8_unicode_ci default NULL,
			  `mkb_id_2` varchar(10) collate utf8_unicode_ci default NULL,
			  `mkb_id_3` varchar(10) collate utf8_unicode_ci default NULL,
			  `mkb_id_4` varchar(10) collate utf8_unicode_ci default NULL,
			  `percent_inv` float default '0',
			  `bad_work_env` varchar(100) collate utf8_unicode_ci default NULL,
			  `date_added` datetime default NULL,
			  `date_modified` datetime default NULL,
			  `first_inv_date` datetime default NULL,
			  PRIMARY KEY  (`telk_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		mysql_query($sql) or die(mysql_error());
	}
	//
	if(in_array('users', $tables)) {
		$sql = "DROP TABLE IF EXISTS `users`";
		mysql_query($sql) or die(mysql_error());
		$sql = "CREATE TABLE IF NOT EXISTS `users` (
			  `user_id` int(11) NOT NULL auto_increment,
			  `user_name` varchar(60) collate utf8_unicode_ci default NULL,
			  `user_pass` varchar(60) collate utf8_unicode_ci default NULL,
			  `user_level` char(1) collate utf8_unicode_ci default NULL,
			  `fname` varchar(60) collate utf8_unicode_ci default NULL,
			  `lname` varchar(60) collate utf8_unicode_ci default NULL,
			  `email` varchar(60) collate utf8_unicode_ci default NULL,
			  `date_created` datetime default NULL,
			  `date_modified` datetime default NULL,
			  `date_last_login` datetime default NULL,
			  `REMOTE_ADDR` varchar(20) collate utf8_unicode_ci default NULL,
			  `hdd` varchar(60) collate utf8_unicode_ci default NULL,
			  PRIMARY KEY  (`user_id`),
			  UNIQUE KEY `user_name` (`user_name`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		mysql_query($sql) or die(mysql_error());
	}
	//
	if(in_array('workers', $tables)) {
		$sql = "DROP TABLE IF EXISTS `workers`";
		mysql_query($sql) or die(mysql_error());
		$sql = "CREATE TABLE IF NOT EXISTS `workers` (
			  `worker_id` int(11) NOT NULL auto_increment,
			  `firm_id` int(11) default '0',
			  `is_active` char(1) collate utf8_unicode_ci default '1',
			  `fname` varchar(60) collate utf8_unicode_ci default NULL,
			  `sname` varchar(60) collate utf8_unicode_ci default NULL,
			  `lname` varchar(60) collate utf8_unicode_ci default NULL,
			  `sex` char(1) collate utf8_unicode_ci default NULL,
			  `egn` char(10) collate utf8_unicode_ci default NULL,
			  `birth_date` datetime default NULL,
			  `location_id` int(11) default '0',
			  `address` varchar(100) collate utf8_unicode_ci default NULL,
			  `phone1` varchar(40) collate utf8_unicode_ci default NULL,
			  `phone2` varchar(40) collate utf8_unicode_ci default NULL,
			  `map_id` int(11) default '0',
			  `date_curr_position_start` datetime default NULL,
			  `date_career_start` datetime default NULL,
			  `date_retired` datetime default NULL,
			  `doctor_id` int(11) default '0',
			  `prchk_author` varchar(100) collate utf8_unicode_ci default NULL,
			  `prchk_date` datetime default NULL,
			  `prchk_anamnesis` text collate utf8_unicode_ci,
			  `prchk_data` text collate utf8_unicode_ci,
			  `prchk_conclusion` char(1) collate utf8_unicode_ci default NULL,
			  `prchk_conditions` text collate utf8_unicode_ci,
			  `prchk_stm_date` datetime default NULL,
			  `prchk_obstetrician` text collate utf8_unicode_ci,
			  `prchk_obstetrician_doc` varchar(255) collate utf8_unicode_ci default NULL,
			  `prchk_dermatologist` text collate utf8_unicode_ci,
			  `prchk_dermatologist_doc` varchar(255) collate utf8_unicode_ci default NULL,
			  `prchk_internal_diseases` text character set utf8 collate utf8_swedish_ci,
			  `prchk_internal_diseases_doc` varchar(255) collate utf8_unicode_ci default NULL,
			  `prchk_ophthalmologist` text collate utf8_unicode_ci,
			  `prchk_ophthalmologist_doc` varchar(255) collate utf8_unicode_ci default NULL,
			  `prchk_pathologist` text collate utf8_unicode_ci,
			  `prchk_pathologist_doc` varchar(255) collate utf8_unicode_ci default NULL,
			  `prchk_UNG` text collate utf8_unicode_ci,
			  `prchk_UNG_doc` varchar(255) collate utf8_unicode_ci default NULL,
			  `prchk_neurologist` text collate utf8_unicode_ci,
			  `prchk_neurologist_doc` varchar(255) collate utf8_unicode_ci default NULL,
			  `prchk_surgeon` text collate utf8_unicode_ci,
			  `prchk_surgeon_doc` varchar(255) collate utf8_unicode_ci default NULL,
			  `prchk_GP` text collate utf8_unicode_ci,
			  `prchk_GP_doc` varchar(255) collate utf8_unicode_ci default NULL,
			  `date_added` datetime default NULL,
			  `date_modified` datetime default NULL,
			  `modified_by` int(11) default '1',
			  `prchk_dentist` text collate utf8_unicode_ci,
			  `prchk_dentist_doc` varchar(255) collate utf8_unicode_ci default NULL,
			  `family_hypertonia` varchar(255) collate utf8_unicode_ci default NULL,
			  `family_heart_disease` varchar(255) collate utf8_unicode_ci default NULL,
			  `family_diabetis` varchar(255) collate utf8_unicode_ci default NULL,
			  `family_other_disease` varchar(255) collate utf8_unicode_ci default NULL,
			  PRIMARY KEY  (`worker_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		mysql_query($sql) or die(mysql_error());
	}
	//
	if(in_array('work_env_protocols', $tables)) {
		$sql = "DROP TABLE IF EXISTS `work_env_protocols`";
		mysql_query($sql) or die(mysql_error());
		$sql = "CREATE TABLE IF NOT EXISTS `work_env_protocols` (
			  `prot_id` int(11) NOT NULL auto_increment,
			  `factor_id` int(11) default '0',
			  `prot_num` varchar(40) collate utf8_unicode_ci default NULL,
			  `prot_date` datetime default NULL,
			  `level` double default '0',
			  PRIMARY KEY  (`prot_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		mysql_query($sql) or die(mysql_error());
	}
	//
	if(in_array('work_places', $tables)) {
		$sql = "DROP TABLE IF EXISTS `work_places`";
		mysql_query($sql) or die(mysql_error());
		$sql = "CREATE TABLE IF NOT EXISTS `work_places` (
			  `wplace_id` int(11) NOT NULL auto_increment,
			  `firm_id` int(11) default '0',
			  `wplace_name` varchar(100) collate utf8_unicode_ci default NULL,
			  `wplace_workcond` text collate utf8_unicode_ci,
			  `wplace_position` int(11) default '1',
			  PRIMARY KEY  (`wplace_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		mysql_query($sql) or die(mysql_error());
	}
	//
	if(in_array('wplace_factors_map', $tables)) {
		$sql = "DROP TABLE IF EXISTS `wplace_factors_map`";
		mysql_query($sql) or die(mysql_error());
		$sql = "CREATE TABLE IF NOT EXISTS `wplace_factors_map` (
			  `map_id` int(11) NOT NULL auto_increment,
			  `firm_id` int(11) default '0',
			  `subdivision_id` int(11) default '0',
			  `wplace_id` int(11) default '0',
			  `fact_dust` text collate utf8_unicode_ci,
			  `fact_chemicals` text collate utf8_unicode_ci,
			  `fact_biological` text collate utf8_unicode_ci,
			  `fact_work_pose` text collate utf8_unicode_ci,
			  `fact_manual_weights` text collate utf8_unicode_ci,
			  `fact_monotony` text collate utf8_unicode_ci,
			  `fact_work_regime` text collate utf8_unicode_ci,
			  `fact_work_hours` text collate utf8_unicode_ci,
			  `fact_work_and_break` text collate utf8_unicode_ci,
			  `fact_nervous` text collate utf8_unicode_ci,
			  `fact_other` text collate utf8_unicode_ci,
			  PRIMARY KEY  (`map_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		mysql_query($sql) or die(mysql_error());
	}
	//
	if(in_array('wplace_prot_map', $tables)) {
		$sql = "DROP TABLE IF EXISTS `wplace_prot_map`";
		mysql_query($sql) or die(mysql_error());
		$sql = "CREATE TABLE IF NOT EXISTS `wplace_prot_map` (
			  `map_id` int(11) NOT NULL auto_increment,
			  `firm_id` int(11) default '0',
			  `subdivision_id` int(11) default '0',
			  `wplace_id` int(11) default '0',
			  `prot_id` int(11) default '0',
			  PRIMARY KEY  (`map_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		mysql_query($sql) or die(mysql_error());
	}
}

















?>