<?php
require('includes.php');

$echoJS = '';
$aServices = getServices();
$aServices[0]['Услуги']['other'] = 'Други';
$aServices = $aServices[0];

$_SESSION[basename($_SERVER['PHP_SELF'])] = (isset($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : '';
$firm_id = (isset($_GET['firm_id']) && is_numeric($_GET['firm_id'])) ? intval($_GET['firm_id']) : 0;
$row = null;
if($firm_id) { $row = $dbInst->getFirmInfo($firm_id); }
if(empty($row)) {
	header('Location: acc_firms.php');
	exit();
}
$contract_id = (isset($_GET['contract_id']) && is_numeric($_GET['contract_id'])) ? intval($_GET['contract_id']) : 0;
if($contract_id) {
	$rows = $dbInst->query("SELECT * FROM `acc_contracts` WHERE `contract_id` = $contract_id");
	if(!empty($rows)) {
		$cntr = $rows[0];
	}
}

$uploadDir = 'docs/';
//Maximum file size. You may increase or decrease.
$MAX_SIZE = 2000000;
//Allowable file Mime Types. Add more mime types if you want
$FILE_MIMES = array('image/jpeg','image/pjpeg','image/jpg','image/gif','image/png');
//Allowable file ext. names. you may add more extension names.
$FILE_EXTS  = array('jpg','png','gif');

// Xajax begin
require ('xajax/xajax_core/xajax.inc.php');
function autofill_due_dates($bill_date = '', $bill_start_date='') {
	$objResponse = new xajaxResponse();
	if(!empty($bill_start_date) && preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{2,4})/', $bill_start_date, $matches)) {
		$day = $matches[1];
		$month = $matches[2];
		$year = $matches[3];
		$due_date = date('d.m.Y', strtotime($year.'-'.$month.'-'.$day));
		if(!empty($bill_date) && preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{2,4})/', $bill_date, $matches)) {
			$d = $matches[1];
			$m = $matches[2];
			$y = $matches[3];
			$due_date = date('d.m.Y', strtotime($y.'-'.$m.'-'.$d));
		}
		if(($month + 3) > 12) {
			$m = ($month + 3) - 12;
			$y = $year + 1;
		} else {
			$m = $month + 3;
			$y = $year;
		}
		$one_day_seconds = 60 * 60 * 24;
		$due_date2 = date('d.m.Y', strtotime($y.'-'.$m.'-'.$day) - $one_day_seconds);
		if(($month + 6) > 12) {
			$m = ($month + 6) - 12;
			$y = $year + 1;
		} else {
			$m = $month + 6;
			$y = $year;
		}
		$due_date3 = date('d.m.Y', strtotime($y.'-'.$m.'-'.$day) - $one_day_seconds);
		if(($month + 9) > 12) {
			$m = ($month + 9) - 12;
			$y = $year + 1;
		} else {
			$m = $month + 9;
			$y = $year;
		}
		$due_date4 = date('d.m.Y', strtotime($y.'-'.$m.'-'.$day) - $one_day_seconds);
		$contract_end_date = date('d.m.Y', strtotime(($year + 1).'-'.$month.'-'.$day) - $one_day_seconds * 1);

		$objResponse->call("fill_in_due_dates", $due_date, $due_date2, $due_date3, $due_date4, $contract_end_date);
	}

	return $objResponse;
}

$xajax = new xajax();
$xajax->registerFunction("autofill_due_dates");
//$xajax->setFlag("debug",true);
$echoJS .= $xajax->getJavascript('xajax/');
$xajax->processRequest();
// Xajax end

//delcontractfile
if(isset($_GET['delcontractfile']) && preg_match('/^contractfile_(\d+)\./', $_GET['delcontractfile'], $matches)) {
	if($contract_id == $matches[1]) {
		$dbInst->query("UPDATE `acc_contracts` SET `contractfile` = '' WHERE `contract_id` = $contract_id");
		if(file_exists($uploadDir.$_GET['delcontractfile'])) {
			@unlink($uploadDir.$_GET['delcontractfile']);
		}
	}
	header('Location: '.basename($_SERVER['PHP_SELF']).'?firm_id='.$firm_id.'&contract_id='.$contract_id);
	exit();
}

if(isset($_POST['btnSubmit'])) {
	$errmsg = array();
	$contract_halt = (isset($_POST['contract_halt'])) ? 1 : 0;
	$contract_num = $dbInst->checkStr($_POST['contract_num']);
	$invoice_num = $dbInst->checkStr($_POST['invoice_num']);
	$d = new ParseBGDate();
	$contract_date = trim($_POST['contract_date']);
	if(empty($contract_date)) {
		$errmsg[] = '- дата на сключване на договора';
	} else {
		if ($d->Parse($contract_date)) { $contract_date = $d->year . '-' . $d->month . '-' . $d->day . ' 00:00:00'; }
		else { $errmsg[] = '- дата на сключване на договора'; }
	}
	$contract_start_date = trim($_POST['contract_start_date']);
	if ($d->Parse($contract_start_date)) { $contract_start_date = $d->year . '-' . $d->month . '-' . $d->day . ' 00:00:00'; }
	else { $contract_start_date = ''; }
	$contract_end_date = trim($_POST['contract_end_date']);
	if ($d->Parse($contract_end_date)) { $contract_end_date = $d->year . '-' . $d->month . '-' . $d->day . ' 00:00:00'; }
	else { $contract_end_date = ''; }
	if(!empty($contract_end_date) && !empty($contract_end_date) && $contract_start_date > $contract_end_date) {
		$errmsg[] = '- крайната дата на договора не може да е преди началната дата';
	}
	$amount_contract = floatval($_POST['amount_contract']);
	//if(empty($amount_contract)) { $errmsg[] = '- сума по договор'; }
	$contract_notes = $dbInst->checkStr($_POST['contract_notes']);
	
	if(count($errmsg)) {
		setFlash('Моля, въведете или коригирайте следните данни: <br />'.implode('<br />', $errmsg));
		header('Location: '.basename($_SERVER['PHP_SELF']).'?firm_id='.$firm_id.'&contract_id='.$contract_id);
		exit();
	}

	$amount_due_total = floatval($_POST['amount_due_total']);
	// Payments
	$due_date = trim($_POST['due_date']);
	if ($d->Parse($due_date)) { $due_date = $d->year . '-' . $d->month . '-' . $d->day . ' 00:00:00'; }
	else { $due_date = ''; }
	$due_date2 = trim($_POST['due_date2']);
	if ($d->Parse($due_date2)) { $due_date2 = $d->year . '-' . $d->month . '-' . $d->day . ' 00:00:00'; }
	else { $due_date2 = ''; }
	$due_date3 = trim($_POST['due_date3']);
	if ($d->Parse($due_date3)) { $due_date3 = $d->year . '-' . $d->month . '-' . $d->day . ' 00:00:00'; }
	else { $due_date3 = ''; }
	$due_date4 = trim($_POST['due_date4']);
	if ($d->Parse($due_date4)) { $due_date4 = $d->year . '-' . $d->month . '-' . $d->day . ' 00:00:00'; }
	else { $due_date4 = ''; }
	$due_date5 = trim($_POST['due_date5']);
	if ($d->Parse($due_date5)) { $due_date5 = $d->year . '-' . $d->month . '-' . $d->day . ' 00:00:00'; }
	else { $due_date5 = ''; }
	$due_date6 = trim($_POST['due_date6']);
	if ($d->Parse($due_date6)) { $due_date6 = $d->year . '-' . $d->month . '-' . $d->day . ' 00:00:00'; }
	else { $due_date6 = ''; }
	$due_date7 = trim($_POST['due_date7']);
	if ($d->Parse($due_date7)) { $due_date7 = $d->year . '-' . $d->month . '-' . $d->day . ' 00:00:00'; }
	else { $due_date7 = ''; }
	$due_date8 = trim($_POST['due_date8']);
	if ($d->Parse($due_date8)) { $due_date8 = $d->year . '-' . $d->month . '-' . $d->day . ' 00:00:00'; }
	else { $due_date8 = ''; }
	$amount_due = floatval($_POST['amount_due']);
	if(empty($amount_due)) { $due_date = ''; }
	$amount_due2 = floatval($_POST['amount_due2']);
	if(empty($amount_due2)) { $due_date2 = ''; }
	$amount_due3 = floatval($_POST['amount_due3']);
	if(empty($amount_due3)) { $due_date3 = ''; }
	$amount_due4 = floatval($_POST['amount_due4']);
	if(empty($amount_due4)) { $due_date4 = ''; }
	$amount_due5 = floatval($_POST['amount_due5']);
	if(empty($amount_due5)) { $due_date5 = ''; }
	$amount_due6 = floatval($_POST['amount_due6']);
	if(empty($amount_due6)) { $due_date6 = ''; }
	$amount_due7 = floatval($_POST['amount_due7']);
	if(empty($amount_due7)) { $due_date7 = ''; }
	$amount_due8 = floatval($_POST['amount_due8']);
	if(empty($amount_due8)) { $due_date8 = ''; }
	if($amount_contract > 0 && !($amount_due + $amount_due2 + $amount_due3 + $amount_due4 + $amount_due5 + $amount_due6 + $amount_due7 + $amount_due8)) {
		$errmsg[] = 'Моля, въведете поне една вноска по общо дължимата сума по договор ('.$amount_due_total.' лв.).';
	} elseif($amount_due_total != ($amount_due + $amount_due2 + $amount_due3 + $amount_due4 + $amount_due5 + $amount_due6 + $amount_due7 + $amount_due8)) {
		$errmsg[] = 'Сумата от вноските ('.($amount_due + $amount_due2 + $amount_due3 + $amount_due4 + $amount_due5 + $amount_due6 + $amount_due7 + $amount_due8).' лв.) се различава от общо дължимата сума по договор ('.$amount_due_total.' лв.)!';
	}

	$paid_date = trim($_POST['paid_date']);
	if ($d->Parse($paid_date)) { $paid_date = $d->year . '-' . $d->month . '-' . $d->day . ' 00:00:00'; }
	else { $paid_date = ''; }
	$paid_date2 = trim($_POST['paid_date2']);
	if ($d->Parse($paid_date2)) { $paid_date2 = $d->year . '-' . $d->month . '-' . $d->day . ' 00:00:00'; }
	else { $paid_date2 = ''; }
	$paid_date3 = trim($_POST['paid_date3']);
	if ($d->Parse($paid_date3)) { $paid_date3 = $d->year . '-' . $d->month . '-' . $d->day . ' 00:00:00'; }
	else { $paid_date3 = ''; }
	$paid_date4 = trim($_POST['paid_date4']);
	if ($d->Parse($paid_date4)) { $paid_date4 = $d->year . '-' . $d->month . '-' . $d->day . ' 00:00:00'; }
	else { $paid_date4 = ''; }
	$paid_date5 = trim($_POST['paid_date5']);
	if ($d->Parse($paid_date5)) { $paid_date5 = $d->year . '-' . $d->month . '-' . $d->day . ' 00:00:00'; }
	else { $paid_date5 = ''; }
	$paid_date6 = trim($_POST['paid_date6']);
	if ($d->Parse($paid_date6)) { $paid_date6 = $d->year . '-' . $d->month . '-' . $d->day . ' 00:00:00'; }
	else { $paid_date6 = ''; }
	$paid_date7 = trim($_POST['paid_date7']);
	if ($d->Parse($paid_date7)) { $paid_date7 = $d->year . '-' . $d->month . '-' . $d->day . ' 00:00:00'; }
	else { $paid_date7 = ''; }
	$paid_date8 = trim($_POST['paid_date8']);
	if ($d->Parse($paid_date8)) { $paid_date8 = $d->year . '-' . $d->month . '-' . $d->day . ' 00:00:00'; }
	else { $paid_date8 = ''; }
	$amount_paid = floatval($_POST['amount_paid']);
	if(empty($amount_paid)) { $paid_date = ''; }
	$amount_paid2 = floatval($_POST['amount_paid2']);
	if(empty($amount_paid2)) { $paid_date2 = ''; }
	$amount_paid3 = floatval($_POST['amount_paid3']);
	if(empty($amount_paid3)) { $paid_date3 = ''; }
	$amount_paid4 = floatval($_POST['amount_paid4']);
	if(empty($amount_paid4)) { $paid_date4 = ''; }
	$amount_paid5 = floatval($_POST['amount_paid5']);
	if(empty($amount_paid5)) { $paid_date5 = ''; }
	$amount_paid6 = floatval($_POST['amount_paid6']);
	if(empty($amount_paid6)) { $paid_date6 = ''; }
	$amount_paid7 = floatval($_POST['amount_paid7']);
	if(empty($amount_paid7)) { $paid_date7 = ''; }
	$amount_paid8 = floatval($_POST['amount_paid8']);
	if(empty($amount_paid8)) { $paid_date8 = ''; }

	/*if($amount_due_total < ($amount_paid + $amount_paid2 + $amount_paid3 + $amount_paid4 + $amount_paid5 + $amount_paid6 + $amount_paid7 + $amount_paid8)) {
	$errmsg[] = 'Сумата от платените вноски ('.($amount_paid + $amount_paid2 + $amount_paid3 + $amount_paid4 + $amount_paid5 + $amount_paid6 + $amount_paid7 + $amount_paid8).' лв.) е по-голяма от общо дължимата застр. премия ('.$amount_due_total.' лв.)!';
	}*/

	if(count($errmsg)) {
		setFlash(implode('<br />', $errmsg));
		header('Location: '.basename($_SERVER['PHP_SELF']).'?firm_id='.$firm_id.'&contract_id='.$contract_id);
		exit();
	}

	$amount_paid_total = $amount_paid + $amount_paid2 + $amount_paid3 + $amount_paid4 + $amount_paid5 + $amount_paid6 + $amount_paid7 + $amount_paid8;

	$amount_items = 0;
	$amount_due_total = $amount_contract + $amount_items;
	$added_by = $updated_by = $_SESSION['sess_user_id'];
	if(!$contract_id) {
		$query = "INSERT INTO `acc_contracts` (`firm_id`, `contract_num`, `invoice_num`, `contract_date`, `contract_start_date`, `contract_end_date`, `amount_contract`, `amount_items`, `amount_due_total`, `amount_paid_total`, `amount_due`, `due_date`, `amount_due2`, `due_date2`, `amount_due3`, `due_date3`, `amount_due4`, `due_date4`, `amount_due5`, `due_date5`, `amount_due6`, `due_date6`, `amount_due7`, `due_date7`, `amount_due8`, `due_date8`, `amount_paid`, `paid_date`, `amount_paid2`, `paid_date2`, `amount_paid3`, `paid_date3`, `amount_paid4`, `paid_date4`, `amount_paid5`, `paid_date5`, `amount_paid6`, `paid_date6`, `amount_paid7`, `paid_date7`, `amount_paid8`, `paid_date8`, `contractfile`, `contract_notes`, `contract_halt`, `added_by`, `added_on`, `updated_by`, `updated_on`) VALUES ('$firm_id', '$contract_num', '$invoice_num', '$contract_date', '$contract_start_date', '$contract_end_date', '$amount_contract', '$amount_items', '$amount_due_total', '$amount_paid_total', '$amount_due', '$due_date', '$amount_due2', '$due_date2', '$amount_due3', '$due_date3', '$amount_due4', '$due_date4', '$amount_due5', '$due_date5', '$amount_due6', '$due_date6', '$amount_due7', '$due_date7', '$amount_due8', '$due_date8', '$amount_paid', '$paid_date', '$amount_paid2', '$paid_date2', '$amount_paid3', '$paid_date3', '$amount_paid4', '$paid_date4', '$amount_paid5', '$paid_date5', '$amount_paid6', '$paid_date6', '$amount_paid7', '$paid_date7', '$amount_paid8', '$paid_date8', '', '$contract_notes', '$contract_halt', '$added_by', datetime('now','localtime'), '$updated_by', datetime('now','localtime'))";
		$contract_id = $dbInst->query($query);
	} else {
		$query = "UPDATE `acc_contracts` SET `contract_num` = '$contract_num', `invoice_num` = '$invoice_num', `contract_date` = '$contract_date', `contract_start_date` = '$contract_start_date', `contract_end_date` = '$contract_end_date', `amount_contract` = '$amount_contract', `amount_items` = '$amount_items', `amount_due_total` = '$amount_due_total', `amount_paid_total` = '$amount_paid_total', `contract_notes` = '$contract_notes', `contract_halt` = '$contract_halt', `updated_by` = '$updated_by', `updated_on` = datetime('now','localtime'), `amount_due` = '$amount_due', `due_date` = '$due_date', `amount_due2` = '$amount_due2', `due_date2` = '$due_date2', `amount_due3` = '$amount_due3', `due_date3` = '$due_date3', `amount_due4` = '$amount_due4', `due_date4` = '$due_date4', `amount_due5` = '$amount_due5', `due_date5` = '$due_date5', `amount_due6` = '$amount_due6', `due_date6` = '$due_date6', `amount_due7` = '$amount_due7', `due_date7` = '$due_date7', `amount_due8` = '$amount_due8', `due_date8` = '$due_date8', `amount_paid` = '$amount_paid', `paid_date` = '$paid_date', `amount_paid2` = '$amount_paid2', `paid_date2` = '$paid_date2', `amount_paid3` = '$amount_paid3', `paid_date3` = '$paid_date3', `amount_paid4` = '$amount_paid4', `paid_date4` = '$paid_date4', `amount_paid5` = '$amount_paid5', `paid_date5` = '$paid_date5', `amount_paid6` = '$amount_paid6', `paid_date6` = '$paid_date6', `amount_paid7` = '$amount_paid7', `paid_date7` = '$paid_date7', `amount_paid8` = '$amount_paid8', `paid_date8` = '$paid_date8' WHERE `contract_id` = $contract_id";
		$dbInst->query($query);
	}

	if(empty($contract_num)) {
		$contract_num = sprintf('%07s', $contract_id);
		$dbInst->query("UPDATE `acc_contracts` SET `contract_num` = '$contract_num' WHERE `contract_id` = $contract_id");
	}

	// Upload docs
	if(isset($_FILES['contractfile']) && $_FILES['contractfile']['size'] > 0) {
		$fname = $_FILES["contractfile"]['name'];
		$ftype = $_FILES["contractfile"]['type'];
		$ftmp_name = $_FILES["contractfile"]['tmp_name'];
		$fsize = $_FILES["contractfile"]['size'];
		$fext = strtolower(substr($fname, (strrpos($fname, '.')+1)));
		//File Size Check
		if ($fsize > $MAX_SIZE) {
			$errmsg[] = '- размерът на файла е над 3 MB.';
		}
		//File Type/Extension Check
		else if (!in_array($ftype, $FILE_MIMES)	&& !in_array($fext, $FILE_EXTS)) {
			$errmsg[] = "- $fname ($ftype) не е разрешено да бъде добавен.";
		}
		if(count($errmsg)) {
			setFlash('Възникнаха следните проблеми при добавяне на файла на договора: <br />'.implode('<br />', $errmsg));
			header('Location: '.basename($_SERVER['PHP_SELF']).'?firm_id='.$firm_id.'&contract_id='.$contract_id);
			exit();
		}

		$contractfile = 'contractfile_'.$contract_id.'.'.$fext;
		make_uploaddir($uploadDir);
		if (move_uploaded_file($ftmp_name, $uploadDir.$contractfile)) {
			$dbInst->query("UPDATE `acc_contracts` SET `contractfile` = '$contractfile' WHERE `contract_id` = $contract_id");
			Resize($uploadDir, $contractfile, $uploadDir, $contractfile, 800, 800, 80);

		} else {
			setFlash('Възникна неочакван проблем при добавяне на файла на договора.');
			header('Location: '.basename($_SERVER['PHP_SELF']).'?firm_id='.$firm_id.'&contract_id='.$contract_id);
			exit();
		}
	}

	// Insert contract items
	$dbInst->query("DELETE FROM `acc_contract_items` WHERE `contract_id` = $contract_id");
	if(isset($_POST['service_type']) && is_array($_POST['service_type'])) {
		for ($i = 0; $i < count($_POST['service_type']); $i++) {
			$position = $i + 1;
			$service_type = $_POST['service_type'][$i];
			$is_actualization = (isset($_POST['is_actualization'][$i])) ? $_POST['is_actualization'][$i] : 0;
			$conclusion = (isset($_POST['conclusion'][$i])) ? $_POST['conclusion'][$i] : 1;
			$start_date = '';
			if(isset($_POST['start_date'][$i])) {
				$start_date = trim($_POST['start_date'][$i]);
				if ($d->Parse($start_date)) { $start_date = $d->year . '-' . $d->month . '-' . $d->day . ' 00:00:00'; }
			}
			$end_date = '';
			if(isset($_POST['end_date'][$i])) {
				$end_date = trim($_POST['end_date'][$i]);
				if ($d->Parse($end_date)) { $end_date = $d->year . '-' . $d->month . '-' . $d->day . ' 00:00:00'; }
			}
			$notes = (isset($_POST['notes'][$i])) ? $dbInst->checkStr($_POST['notes'][$i]) : '';
			$amount = (isset($_POST['amount'][$i])) ? floatval($_POST['amount'][$i]) : 0;
			$amount_items += $amount;
			$query = "INSERT INTO `acc_contract_items` (`contract_id`, `position`, `service_type`, `is_actualization`, `conclusion`, `start_date`, `end_date`, `notes`, `amount`) VALUES ('$contract_id', '$position', '$service_type', '$is_actualization', '$conclusion', '$start_date', '$end_date', '$notes', '$amount')";
			$dbInst->query($query);
		}
	}
	$dbInst->query("UPDATE `acc_contracts` SET `amount_items` = $amount_items, `amount_due_total` = (`amount_contract` + $amount_items) WHERE `contract_id` = $contract_id");

	header('Location: '.basename($_SERVER['PHP_SELF']).'?firm_id='.$firm_id.'&contract_id='.$contract_id);
	exit();
}

$aCond = array();
//Ex.: "serve" == service_type || "train" == service_type || "estimate" == service_type || "plans" == service_type
foreach ($aServices['Услуги'] as $key => $value) {
	$aCond[] = '"'.$key.'" == service_type';
}
$cond = implode(' || ', $aCond);

$echoJS .= <<< EOT
<!-- http://jonathanleighton.com/projects/date-input -->
<script type="text/javascript" src="js/date_input/jquery.date_input.js"></script>
<link rel="stylesheet" href="js/date_input/date_input.css" type="text/css"/>
<script type="text/javascript">
//<![CDATA[
function acc_loadCalendar() {
	jQuery.extend(DateInput.DEFAULT_OPTS, {
	  month_names: ["Януари","Февруари","Март","Април","Май","Юни","Юли","Август","Септември","Октомври","Ноември","Декември"],
	  short_month_names: ["Яну","Фев","Мар","Апр","Май","Юни","Юли","Авг","Сеп","Окт","Ное","Дек"],
	  short_day_names: ["Нд", "Пн", "Вт", "Ср", "Чт", "Пт", "Сб"]
	});
	$($.date_input.initialize);
}
$(document).ready(function() {
	// Strip table
	//$("table.carlist tr:odd").addClass("selected");
	// Hightlight table rows
	$("table.highlight tr").not(".notover").hover(function() {
		$(this).addClass("tr_highlight");
	},function() {
		$(this).removeClass("tr_highlight");
	});
	acc_loadCalendar();
	$("a#lnkAdd").click(function(){
		var service_type = document.getElementById('service_type_opt').value;
		var service_desc = document.getElementById('service_type_opt').options[document.getElementById('service_type_opt').selectedIndex].text;
		if('' == service_type) {
			alert("Моля, изберете услуга от списъка.");
		} else {
			var str = '';
			if("annex" == service_type) {
				str += acc_getAnnex();
			} else if($cond) {
				str += acc_getServices(service_type, service_desc);
			} else {
				str += acc_getMeasurings(service_type, service_desc);
			}
			$("div#servicesWrapper").append(str);
			acc_loadCalendar();
		}
		return false;	  
	});
	
	$("input#contract_start_date").change(function(){
		if(this.value != "") {
			xajax_autofill_due_dates($("input#contract_date").val(), this.value);
			return false;
		}
	});
	$("input#amount_contract").change(function(){
		calc_amount_due_total();
	});
	$("input[name='amount[]']").change(function(){
		calc_amount_due_total();
	});
});

// Dyn. items

function acc_getAnnex() {
	var str = '<div class="contract_item">';
	str += '<input type="hidden" name="service_type[]" value="annex" \/>'; 
	str += '<table class="listing" cellpadding="4" cellspacing="0" width="99%">';
	str += '<tr>';
	str += '<th colspan="5">Анекс към договора<\/th>';
	str += '<\/tr>';
	str += '<tr>';
	str += '<td>Дата: <input type="text" name="start_date[]" value="" size="12" class="date_input" \/> г.<\/td>';
	str += '<td>Бележки: <\/td>';
	str += '<td><textarea name="notes[]" rows="2" cols="40"><\/textarea><\/td>';
	str += '<td align="right">Сума: <input type="text" name="amount[]" value="" size="6" onchange="calc_amount_due_total();" \/> лв.<\/td>';
	str += '<td width="30" align="right"><a href="javascript:void(null);" onclick="$(this).parent().parent().parent().parent().parent().remove();"> X <\/a><\/td>';
	str += '<\/tr>';
	str += '<\/table>';
	str += '<\/div>';
	return str;
}
function acc_getMeasurings(service_type, service_desc) {
	var str = '<div class="contract_item">';
	str += '<input type="hidden" name="service_type[]" value="'+service_type+'" \/>';
	str += '<table class="listing" cellpadding="4" cellspacing="0" width="99%">';
	str += '<tr>';
	str += '<th colspan="6">Измерване '+service_desc+'<\/th>';
	str += '<\/tr>';
	str += '<tr>';
	str += '<td>Актуализация? <select name="is_actualization[]">';
	str += '<option value=""> &nbsp;&nbsp;<\/option>';
	str += '<option value="0">Не &nbsp;&nbsp;<\/option>';
	str += '<option value="1">Да &nbsp;&nbsp;<\/option>';
	str += '<\/select><\/td>';
	str += '<td>Заключение: <select name="conclusion[]">';
	str += '<option value=""> &nbsp;&nbsp;<\/option>';
	str += '<option value="1">Съответства &nbsp;&nbsp;<\/option>';
	str += '<option value="0">Не съответства &nbsp;&nbsp;<\/option>';
	str += '<\/select><\/td>';
	str += '<td nowrap="nowrap">Изд. на: <input type="text" name="start_date[]" value="" size="12" class="date_input" \/> г.<\/td>';
	str += '<td nowrap="nowrap">Валидно до: <input type="text" name="end_date[]" value="" size="12" class="date_input" \/> г.<\/td>';
	str += '<td align="right nowrap="nowrap"">Сума: <input type="text" name="amount[]" value="" size="6" onchange="calc_amount_due_total();" \/> лв.<\/td>';
	str += '<td width="30" align="right"><a href="javascript:void(null);" onclick="$(this).parent().parent().parent().parent().parent().remove();"> X <\/a><\/td>';	
	str += '<\/tr>';
	str += '<\/table>';
	str += '<\/div>';
	return str;
}
function acc_getServices(service_type, service_desc) {
	var str = '<div class="contract_item">';
	str += '<input type="hidden" name="service_type[]" value="'+service_type+'" \/>';
	str += '<table class="listing" cellpadding="4" cellspacing="0" width="99%">';
	str += '<tr>';
	str += '<th colspan="3">'+service_desc+'<\/th>';
	str += '<\/tr>';
	str += '<tr>';
	str += '<td>Дата: <input type="text" name="start_date[]" value="" size="12" class="date_input" \/> г.<\/td>';
	str += '<td>Бележки: <\/td>';
    str += '<td><textarea name="notes[]" rows="2" cols="40"><\/textarea><\/td>';	
	str += '<td align="right">Сума: <input type="text" name="amount[]" value="" size="6" onchange="calc_amount_due_total();" \/> лв.<\/td>';
	str += '<td width="30" align="right"><a href="javascript:void(null);" onclick="$(this).parent().parent().parent().parent().parent().remove();"> X <\/a><\/td>';	
	str += '<\/tr>';
	str += '<\/table>';
	str += '<\/div>';
	return str;
}
function validate(form) {
	if(!validateNotEmpty(form.contract_date.value)) {
		alert("Моля, въведете дата на сключване на договора.");
		form.contract_date.focus();
		return false;
	}
	if(form.amount_contract.value == '') form.amount_contract.value = 0;
	/*if(form.amount_contract.value == 0 || form.amount_contract.value == '') {
		alert("Моля, въведете сума по договор.");
		form.amount_contract.focus();
		return false;
	}*/
	
	if(form.amount_contract.value > 0) {
		calc_amount_due_total();
		
		var total = 0;
		$("input[id^='amount_due']").each(function(){
			if(this.id != 'amount_due_total' && parseFloat(this.value) > 0) {
				total += parseFloat(this.value);
			}
		});
		if(!total) {
			alert("Моля, въведете поне една вноска по общо дължимата сума по договор ("+$("input#amount_due_total").val()+" лв.).");
			form.amount_due.focus();
			return false;
		}
		if(total != $("input#amount_due_total").val()) {
			alert("Сумата от вноските ("+total+" лв.) се различава от общо дължимата сума по договор ("+$("input#amount_due_total").val()+" лв.)!");
			form.amount_due.focus();
			return false;
		}
	}
}
function fill_in_due_dates(due_date, due_date2, due_date3, due_date4, contract_end_date) {
	if($("input#contract_end_date").val() == "") {
		$("input#contract_end_date").val(contract_end_date);
	}
	if($("input#due_date").val() == "") {
		$("input#due_date").val(due_date);
	}
	if($("input#due_date2").val() == "") {
		$("input#due_date2").val(due_date2);
	}
	if($("input#due_date3").val() == "") {
		$("input#due_date3").val(due_date3);
	}
	if($("input#due_date4").val() == "") {
		$("input#due_date4").val(due_date4);
	}
}
function calc_amount_due_total() {
	var amount_due_total = 0;
	amount_due_total += (parseFloat( $("input[name='amount_contract']").val() ) > 0) ? parseFloat( $("input[name='amount_contract']").val() ) : 0;
	$("input[name='amount[]']").each(function(){
		if(parseFloat(this.value) > 0) {
			amount_due_total += parseFloat(this.value);
		}
	});
	
	$("input#amount_due_total").val(amount_due_total);
	$("span#span_amount_due_total").html(amount_due_total.toFixed(2));
}
//]]>
</script>
EOT;

include("acc_header.php");
?>
    <div class="breadcrumbs"><a href="acc_firms.php<?=((isset($_SESSION['acc_firms.php'])&&!empty($_SESSION['acc_firms.php']))?'?'.$_SESSION['acc_firms.php']:'')?>">Списък фирми</a> &raquo; <a href="acc_firm_info.php?firm_id=<?=$firm_id?>"><?=HTMLFormat($row['name'])?></a> &raquo; <?=((isset($cntr['contract_num']))?'Договор No '.HTMLFormat($cntr['contract_num']):'Нов договор')?></div>
    <?php if('' != ($msg = getFlash())) { ?>
    <div class="err"><?=$msg?></div>
    <?php } ?>
    <form id="frmFirm" action="<?=basename($_SERVER['PHP_SELF'])?>?firm_id=<?=$firm_id?>&amp;contract_id=<?=$contract_id?>" method="post" enctype="multipart/form-data" onsubmit="return validate(this);">
      <input type="hidden" id="amount_due_total" name="amount_due_total" value="<?=((isset($cntr['amount_due_total']))?HTMLFormat($cntr['amount_due_total']):'')?>" />
      <h3>Данни по договора</h3>
      <table class="listing" cellpadding="4" cellspacing="0" width="99%">
        <?php if($contract_id) { ?>
        <tr>
          <td>&nbsp;</td>
          <td><input type="checkbox" id="contract_halt" name="contract_halt" value="1"<?=((isset($cntr['contract_halt'])&&'1'==$cntr['contract_halt'])?' checked="checked"':'')?> /> Прекрати договора</td>
        </tr>
        <?php } ?>
        <tr>
          <td>Договор No</td>
          <td><input type="text" id="contract_num" name="contract_num" value="<?=((isset($cntr['contract_num']))?HTMLFormat($cntr['contract_num']):'')?>" size="48" maxlength="255" /></td>
        </tr>
        <tr>
          <td>Фактура No</td>
          <td><input type="text" id="invoice_num" name="invoice_num" value="<?=((isset($cntr['invoice_num']))?HTMLFormat($cntr['invoice_num']):'')?>" size="48" maxlength="255" /></td>
        </tr>
        <tr>
          <td>Дата на сключване* </td>
          <td><input type="text" id="contract_date" name="contract_date" value="<?=((isset($cntr['contract_date'])&&!empty($cntr['contract_date'])&&false!==$timestamp=strtotime($cntr['contract_date']))?date('d.m.Y',$timestamp):'')?>" size="15" class="date_input" />
            г.</td>
        </tr>
        <tr>
          <td>Срок на договора:</td>
          <td>От
            <input type="text" id="contract_start_date" name="contract_start_date" value="<?=((isset($cntr['contract_start_date'])&&!empty($cntr['contract_start_date'])&&false!==$timestamp=strtotime($cntr['contract_start_date']))?date('d.m.Y',$timestamp):'')?>" size="15" class="date_input" />
            г. до
            <input type="text" id="contract_end_date" name="contract_end_date" value="<?=((isset($cntr['contract_end_date'])&&!empty($cntr['contract_end_date'])&&false!==$timestamp=strtotime($cntr['contract_end_date']))?date('d.m.Y',$timestamp):'')?>" size="15" class="date_input" />
            г.</td>
        </tr>
        <tr>
          <td>Сума по договор* </td>
          <td><input type="text" id="amount_contract" name="amount_contract" value="<?=((isset($cntr['amount_contract']))?number_format($cntr['amount_contract'], 2, '.',''):'')?>" size="15" onchange="calc_amount_due_total();" />
            лв.</td>
        </tr>
        <tr>
          <td>Копие на договора </td>
          <td><input type="file" id="contractfile" name="contractfile" size="15" />
          	<?php 
          	if(isset($cntr['contractfile']) && !empty($cntr['contractfile']) && file_exists($uploadDir.$cntr['contractfile'])) {
          		list($width, $height, $type, $attr) = getimagesize($uploadDir.$cntr['contractfile']);
          	?>
            | <a href="thumb.php?i=<?=$uploadDir.$cntr['contractfile'].'&amp;maxwidth='.$width.'&amp;maxheight='.$height?>" target="_blank">Отвори файла</a> | <a href="<?=basename($_SERVER['PHP_SELF']).'?delcontractfile='.$cntr['contractfile'].'&amp;firm_id='.$firm_id.'&amp;contract_id='.$contract_id?>" onclick="if(!confirm('Наистина ли искате да изтриете електронното копие на договора?')){return false;}"> X </a><?php } ?></td>
        </tr>
        <tr>
          <td>Бележки</td>
          <td><textarea id="contract_notes" name="contract_notes" cols="40" rows="3" style="width:358px"><?=((isset($cntr['contract_notes']))?HTMLFormat($cntr['contract_notes']):'')?></textarea></td>
        </tr>
        <tr>
          <td>&nbsp;</td>
          <td><input type="submit" id="btnSubmit" name="btnSubmit" value="Съхрани" class="nicerButton" /></td>
        </tr>
      </table>
      <div class="divider1">&nbsp;</div>
      <h3>Услуги</h3>
      <div id="servicesWrapper">
		<?php
		if(!empty($contract_id)) {
			$items = $dbInst->query("SELECT * FROM `acc_contract_items` WHERE `contract_id` = $contract_id ORDER BY `position`");
			if(!empty($items)) {
				foreach ($items as $item) {
				?>
        <div class="contract_item">
		  <?php if('annex' == $item['service_type']) { ?>
          <input type="hidden" name="service_type[]" value="annex" />
          <input type="hidden" name="is_actualization[]" value="0" />
          <input type="hidden" name="conclusion[]" value="1" />
          <input type="hidden" name="end_date[]" value="" />
          <table class="listing" cellpadding="4" cellspacing="0" width="99%">
            <tr>
              <th colspan="5"><?=$aServices[''][$item['service_type']]?></th>
            </tr>
            <tr>
              <td>Дата:
                <input type="text" name="start_date[]" value="<?=((!empty($item['start_date'])&&false!==$timestamp=strtotime($item['start_date']))?date('d.m.Y',$timestamp):'')?>" size="12" class="date_input" />
                г.</td>
              <td>Бележки: </td>
              <td><textarea name="notes[]" rows="2" cols="40"><?=HTMLFormat($item['notes'])?></textarea></td>
              <td align="right">Сума:
                <input type="text" name="amount[]" value="<?=number_format($item['amount'], 2, '.', '')?>" size="6" onchange="calc_amount_due_total();" />
                лв.</td>
              <td width="30" align="right"><a href="javascript:void(null);" onclick="$(this).parent().parent().parent().parent().parent().remove();"> X </a></td>
            </tr>
          </table>
		  <?php /*} elseif (in_array($item['service_type'], array('serve', 'train', 'estimate', 'plans'))) {*/ ?>
		  <?php } elseif (array_key_exists($item['service_type'], $aServices['Услуги'])) { ?>
          <input type="hidden" name="service_type[]" value="<?=$item['service_type']?>" />
          <input type="hidden" name="is_actualization[]" value="0" />
          <input type="hidden" name="conclusion[]" value="1" />
          <input type="hidden" name="end_date[]" value="" />
          <table class="listing" cellpadding="4" cellspacing="0" width="99%">
            <tr>
              <th colspan="6"><?=$aServices['Услуги'][$item['service_type']]?></th>
            </tr>
            <tr>
              <td>Дата:
                <input type="text" name="start_date[]" value="<?=((!empty($item['start_date'])&&false!==$timestamp=strtotime($item['start_date']))?date('d.m.Y',$timestamp):'')?>" size="12" class="date_input" />
                г.</td>
              <td>Бележки: </td>
              <td><textarea name="notes[]" rows="2" cols="40"><?=HTMLFormat($item['notes'])?></textarea></td>
              <td align="right">Сума:
                <input type="text" name="amount[]" value="<?=number_format($item['amount'], 2, '.', '')?>" size="6" onchange="calc_amount_due_total();" />
                лв.</td>
              <td width="30" align="right"><a href="javascript:void(null);" onclick="$(this).parent().parent().parent().parent().parent().remove();"> X </a></td>
            </tr>
          </table>
		  <?php } else { ?>
          <input type="hidden" name="service_type[]" value="<?=$item['service_type']?>" />
          <input type="hidden" name="notes[]" value="" />
          <table class="listing" cellpadding="4" cellspacing="0" width="99%">
            <tr>
              <th colspan="6">Измерване <?=$aServices['Измервания'][$item['service_type']]?></th>
            </tr>
            <tr>
              <td>Актуализация?
                <select name="is_actualization[]">
                  <option value=""> &nbsp;&nbsp;</option>
                  <option value="0"<?=((!strcmp('0', $item['is_actualization']))?' selected="selected"':'')?>>Не &nbsp;&nbsp;</option>
                  <option value="1"<?=((!strcmp('1', $item['is_actualization']))?' selected="selected"':'')?>>Да &nbsp;&nbsp;</option>
                </select>
              </td>
              <td>Заключение:
                <select name="conclusion[]">
                  <option value=""> &nbsp;&nbsp;</option>
                  <option value="1"<?=((!strcmp('1', $item['conclusion']))?' selected="selected"':'')?>>Съответства &nbsp;&nbsp;</option>
                  <option value="0"<?=((!strcmp('0', $item['conclusion']))?' selected="selected"':'')?>>Не съответства &nbsp;&nbsp;</option>
                </select>
              </td>
              <td nowrap="nowrap">Изд. на:
                <input type="text" name="start_date[]" value="<?=((!empty($item['start_date'])&&false!==$timestamp=strtotime($item['start_date']))?date('d.m.Y',$timestamp):'')?>" size="12" class="date_input" />
                г.</td>
              <td nowrap="nowrap">Валидно до:
                <input type="text" name="end_date[]" value="<?=((!empty($item['end_date'])&&false!==$timestamp=strtotime($item['end_date']))?date('d.m.Y',$timestamp):'')?>" size="12" class="date_input" />
                г.</td>
              <td align="right" nowrap="nowrap">Сума:
                <input type="text" name="amount[]" value="<?=number_format($item['amount'], 2, '.', '')?>" size="6" onchange="calc_amount_due_total();" />
                лв.</td>
              <td width="30" align="right"><a href="javascript:void(null);" onclick="$(this).parent().parent().parent().parent().parent().remove();"> X </a></td>
            </tr>
          </table>
		  <?php } ?>
        </div>
		<?php
				}
			}
		}
		?>
      </div>
      <div class="divider1">&nbsp;</div>
      <table class="listing" cellpadding="4" cellspacing="0" width="99%">
        <tr>
          <td><select id="service_type_opt" name="service_type_opt">
              <option value="">-- избери от списъка -- </option>
              <?=getServicesPulldownOptions()?>
            </select>
            <a id="lnkAdd" href="#" title="Добави услуга"> Добави </a> </td>
        </tr>
      </table>
      <div class="divider1">&nbsp;</div>
      <h3>Падежи и плащания</h3>
      <a name="payments" id="payments"></a>
      <table class="listing" cellpadding="4" cellspacing="0" width="99%">
        <tr>
          <th>No &nbsp; </th>
          <th>Дата на падежа &nbsp; </th>
          <th>Вноска* &nbsp; </th>
          <th>Дата на плащане &nbsp; </th>
          <th>Платена сума &nbsp; </th>
        </tr>
        <?php 
        $aNumbers = array('1' => 'I', '2' => 'II', '3' => 'III', '4' => 'IV', '5' => 'V', '6' => 'VI', '7' => 'VII', '8' => 'VIII', '9' => 'IX', '10' => 'X');
        for ($i = 1; $i <= 8; $i++) { 
        	$suff = (1 == $i) ? '' : $i;
        	?>
        <tr>
          <td align="center"><?=$aNumbers[$i]?>. &nbsp;</td>
          <td align="center"><input type="text" id="due_date<?=$suff?>" name="due_date<?=$suff?>" value="<?=((isset($cntr['due_date'.$suff])&&!empty($cntr['due_date'.$suff]))?date('d.m.Y', strtotime($cntr['due_date'.$suff])):'')?>" class="date_input" size="15" />
            г. &nbsp; </td>
          <td align="center"><input type="text" id="amount_due<?=$suff?>" name="amount_due<?=$suff?>" value="<?=((isset($cntr['amount_due'.$suff]))?HTMLFormat($cntr['amount_due'.$suff]):'')?>" onkeypress="return numbersonly(this, event, 1);" size="15" />
            лв.
            &nbsp; </td>
          <td align="center"><input type="text" id="paid_date<?=$suff?>" name="paid_date<?=$suff?>" value="<?=((isset($cntr['paid_date'.$suff])&&!empty($cntr['paid_date'.$suff]))?date('d.m.Y', strtotime($cntr['paid_date'.$suff])):'')?>" class="date_input" size="15" />
            г. &nbsp; </td>
          <td align="center"><input type="text" id="amount_paid<?=$suff?>" name="amount_paid<?=$suff?>" value="<?=((isset($cntr['amount_paid'.$suff]))?HTMLFormat($cntr['amount_paid'.$suff]):'')?>" onkeypress="return numbersonly(this, event, 1);" size="15" />
            лв.
            &nbsp; </td>
        </tr>
        <?php } ?>        
        <tr>
          <td>&nbsp;</td>
          <td colspan="2"><h3>Общо дължимо: &nbsp;<span id="span_amount_due_total"><?=((isset($cntr['amount_due_total']))?number_format($cntr['amount_due_total'], 2, '.',' '):'0.00')?></span> лв. </h3></td>
          <td colspan="2" align="right"><h3>Общо платено: &nbsp; <?=((isset($cntr['amount_paid_total']))?number_format($cntr['amount_paid_total'], 2, '.',' '):'0.00')?> лв. / Остатък: <?=((isset($cntr['amount_paid_total']))?number_format(($cntr['amount_due_total']-$cntr['amount_paid_total']), 2, '.',' '):'0.00')?> лв.</h3></td>
        </tr>
      </table>
      <p>&nbsp;</p>
    </form>

<?php include("acc_footer.php"); ?>