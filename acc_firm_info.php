<?php
require('includes.php');

$echoJS = '';
$uploadDir = 'docs/';
$_SESSION[basename($_SERVER['PHP_SELF'])] = (isset($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : '';
$firm_id = (isset($_GET['firm_id']) && is_numeric($_GET['firm_id'])) ? intval($_GET['firm_id']) : 0;
if($firm_id) { $row = $dbInst->getFirmInfo($firm_id); }

//delete contract
if(isset($_GET['del']) && !empty($_GET['del']) && is_numeric($_GET['del']) && in_array($_SESSION['sess_access_level'], array(1))) {
	$rows = $dbInst->query(sprintf("SELECT * FROM `acc_contracts` WHERE `contract_id` = %d", $_GET['del']));
	if (!empty($rows)) {
		$row = $rows[0];
		if(file_exists($uploadDir.$row['contractfile'])) {
			@unlink($uploadDir.$row['contractfile']);
		}
		$dbInst->query(sprintf("DELETE FROM `acc_contracts` WHERE `contract_id` = %d", $_GET['del']));
	}
	header('Location: '.basename($_SERVER['PHP_SELF']).'?firm_id='.$firm_id);
	exit();
}

// Xajax begin
require ('xajax/xajax_core/xajax.inc.php');
function processFirm($aFormValues) {
	$objResponse = new xajaxResponse();

	$objResponse->assign("btnSubmit", "disabled", false);
	$objResponse->assign("btnSubmit", "value", "Съхрани");
	$objResponse->call("DisableEnableForm", false);

	if (trim($aFormValues['name']) == '') {
		$objResponse->alert("Моля, въведете наименование на фирмата.");
		return $objResponse;
	}
	if ($aFormValues['email'] != '' && !EMailIsCorrect($aFormValues['email'])) {
		$objResponse->alert("$aFormValues[email] е невалиден e-mail адрес!");
		return $objResponse;
	}
	if (!intval($aFormValues['location_id']) && trim($aFormValues['location_name']) == '') {
		$objResponse->assign("location_name", "value", "");
		$objResponse->assign("location_id", "value", 0);
	}
	if (!intval($aFormValues['community_id']) && trim($aFormValues['community_name']) == '') {
		$objResponse->assign("community_name", "value", "");
		$objResponse->assign("community_id", "value", 0);
	}
	if (!intval($aFormValues['province_id']) && trim($aFormValues['province_name']) == '') {
		$objResponse->assign("province_name", "value", "");
		$objResponse->assign("province_id", "value", 0);
	}

	global $dbInst;
	global $firm_id;
	$modified_by = $_SESSION['sess_user_id'];
	$var_list = array('firm_id' => 'firm_id', 'name' => 'name', 'bulstat' => 'bulstat', 'location_name' => 'location_name', 'location_id' => 'location_id', 'community_name' => 'community_name', 'community_id' => 'community_id', 'province_name' => 'province_name', 'province_id' => 'province_id', 'address' => 'address', 'email' => 'email', 'notes' => 'notes', 'phone1' => 'phone1', 'phone2' => 'phone2', 'fax' => 'fax');
	while (list($var, $param) = @each($var_list)) {
		if (isset($aFormValues[$param]))
		$$var = $dbInst->checkStr($aFormValues[$param]);
	} //end while

	if ($location_name == '')
	$location_id = 0;
	if ($community_name == '')
	$community_id = 0;
	if ($province_name == '')
	$province_id = 0;
	if ($firm_id) { // Update firm
		$query = "UPDATE `firms` SET `name` = '$name', `bulstat` = '$bulstat', location_id = '" . intval($location_id) . "', `community_id` = '" . intval($community_id) . "', `province_id` = '" . intval($province_id) . "', `address` = '$address', `email` = '$email', `notes` = '$notes', `phone1` = '$phone1', `phone2` = '$phone2', `fax` = '$fax', `date_modified` = datetime('now','localtime'), `modified_by` = '$modified_by' WHERE firm_id = '$firm_id'";
		$dbInst->query($query);
	} else { // Insert firm
		$query = "INSERT INTO `firms` (`name`, `bulstat`, `location_id`, `community_id`, `province_id`, `address`, `email`, `notes`, `phone1`, `phone2`, `fax`, `date_added`, `date_modified`, `modified_by`) VALUES ('$name', '$bulstat', '" .	intval($location_id) . "', '" . intval($community_id) . "', '" . intval($province_id) . "', '$address', '$email', '$notes', '$phone1', '$phone2', '$fax', datetime('now','localtime'), datetime('now','localtime'), '$modified_by')";
		$firm_id = $dbInst->query($query);
	}

	$objResponse->assign("firm_id", "value", $firm_id);
	$objResponse->assign("ftitle", "innerHTML", trim($aFormValues['name']));

	return $objResponse;
}
$xajax = new xajax();
$xajax->registerFunction("processFirm");
$xajax->registerFunction("guessLocation");
$xajax->registerFunction("guessCommunity");
$xajax->registerFunction("guessProvince");
//$xajax->setFlag("debug",true);
$echoJS = $xajax->getJavascript('xajax/');
$xajax->processRequest();
// Xajax end

$echoJS .= <<< EOT
<!-- Auto-completer begin -->
<!-- http://dev.jquery.com/view/trunk/plugins/autocomplete/ -->
<!-- <script type="text/javascript" src="js/autocompleter/jquery.js"></script> -->
<script type='text/javascript' src='js/autocompleter/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/autocompleter/jquery.dimensions.js'></script>
<script type='text/javascript' src='js/autocompleter/jquery.ajaxQueue.js'></script>
<script type='text/javascript' src='js/autocompleter/jquery.autocomplete.js'></script>
<script type='text/javascript' src='js/autocompleter/localdata.js'></script>
<!-- <link rel="stylesheet" type="text/css" href="js/autocompleter/main.css" /> -->
<link rel="stylesheet" type="text/css" href="js/autocompleter/jquery.autocomplete.css" />
<script type="text/javascript">
//<![CDATA[
$(document).ready(function() {
	// Strip table
	//$("table.carlist tr:odd").addClass("selected");
	// Hightlight table rows
	$("table.highlight tr").not(".notover").hover(function() {
		$(this).addClass("tr_highlight");
	},function() {
		$(this).removeClass("tr_highlight");
	});

	function findValueCallback(event, data, formatted) {
		$("<li>").html( !data ? "No match!" : "Selected: " + formatted).appendTo("#result");
	}
	function formatItem(row) {
		return row[0] + " (<strong>id: " + row[1] + "<\/strong>)";
	}
	function formatResult(row) {
		return row[0].replace(/(<.+?>)/gi, '');
	}
	$(":text, textarea").result(findValueCallback).next().click(function() {
		$(this).prev().search();
	});

	$("#province_name").autocomplete("autocompleter.php", {
		minChars: 0,
		extraParams: { search: "provinces" },
		width: 260,
		scroll: true,
		scrollHeight: 300,
		selectFirst: false
	});
	$("#province_name").result(function(event, data, formatted) {
		if (data) $("#province_id").val(data[1]);
	});

	$("#community_name").autocomplete("autocompleter.php", {
		minChars: 0,
		extraParams: { search: "communities" },
		width: 260,
		scroll: true,
		scrollHeight: 300,
		selectFirst: false
	});
	$("#community_name").result(function(event, data, formatted) {
		if (data) $("#community_id").val(data[1]);
	});

	$("#location_name").autocomplete("autocompleter.php", {
		minChars: 0,
		extraParams: { search: "locations" },
		width: 260,
		scroll: true,
		scrollHeight: 300,
		selectFirst: false
	});
	$("#location_name").result(function(event, data, formatted) {
		if (data) $("#location_id").val(data[1]);
	});
});
//]]>
</script>
<!-- Auto-completer end -->
EOT;

include("acc_header.php");
?>
    <div class="breadcrumbs"><a href="acc_firms.php<?=((isset($_SESSION['acc_firms.php'])&&!empty($_SESSION['acc_firms.php']))?'?'.$_SESSION['acc_firms.php']:'')?>">Списък фирми</a> &raquo; <span id="ftitle"><?=((isset($row['name']))?HTMLFormat($row['name']):'Нова фирма')?></span></div>
    <form id="frmFirm" action="javascript:void(null);">
      <input type="hidden" id="firm_id" name="firm_id" value="<?=$firm_id?>" />
      <h3>Основна информация за фирмата</h3>
      <table class="listing" cellpadding="4" cellspacing="0" width="99%">
        <tr>
          <td>Наименование*</td>
          <td><input type="text" id="name" name="name" value="<?=((isset($row['name']))?HTMLFormat($row['name']):'')?>" size="48" maxlength="255" /></td>
        </tr>
        <tr>
          <td>ЕГН/ЕИК/БУЛСТАТ </td>
          <td><input type="text" id="bulstat" name="bulstat" value="<?=((isset($row['bulstat']))?HTMLFormat($row['bulstat']):'')?>" size="48" maxlength="255" /></td>
        </tr>
        <tr>
          <td>Населено място (гр./с.)</td>
          <td><input type="text" id="location_name" name="location_name" value="<?=((isset($row['location_name']))?HTMLFormat($row['location_name']):'')?>" size="48" maxlength="255" onchange="xajax_guessLocation(this.value);return false;" />
            <input type="hidden" id="location_id" name="location_id" value="<?=((!isset($row['location_id'])||$row['location_id']=='')?'0':$row['location_id'])?>" /></td>
        </tr>
        <tr>
          <td>Община</td>
          <td><input type="text" id="community_name" name="community_name" value="<?=((isset($row['community_name']))?HTMLFormat($row['community_name']):'')?>" size="48" maxlength="255" onchange="xajax_guessCommunity(this.value);return false;" />
            <input type="hidden" id="community_id" name="community_id" value="<?=((!isset($row['community_id'])||$row['community_id']=='')?'0':$row['community_id'])?>" /></td>
        </tr>
        <tr>
          <td>Област</td>
          <td><input type="text" id="province_name" name="province_name" value="<?=((isset($row['province_name']))?HTMLFormat($row['province_name']):'')?>" size="48" maxlength="255" onchange="xajax_guessProvince(this.value);return false;" />
            <input type="hidden" id="province_id" name="province_id" value="<?=((!isset($row['province_id'])||$row['province_id']=='')?'0':$row['province_id'])?>" /></td>
        </tr>
        <tr>
          <td>Адрес (ул./жк.)</td>
          <td><input type="text" id="address" name="address" value="<?=((isset($row['address']))?HTMLFormat($row['address']):'')?>" size="48" maxlength="255" /></td>
        </tr>
        <tr>
          <td>Тел. 1</td>
          <td><input type="text" id="phone1" name="phone1" value="<?=((isset($row['phone1']))?HTMLFormat($row['phone1']):'')?>" size="48" maxlength="255" /></td>
        </tr>
        <tr>
          <td>Тел. 2</td>
          <td><input type="text" id="phone2" name="phone2" value="<?=((isset($row['phone2']))?HTMLFormat($row['phone2']):'')?>" size="48" maxlength="255" /></td>
        </tr>
        <tr>
          <td>Факс</td>
          <td><input type="text" id="fax" name="fax" value="<?=((isset($row['fax']))?HTMLFormat($row['fax']):'')?>" size="48" maxlength="255" /></td>
        </tr>
        <tr>
          <td>E-mail</td>
          <td><input type="text" id="email" name="email" value="<?=((isset($row['email']))?HTMLFormat($row['email']):'')?>" size="48" maxlength="255" /></td>
        </tr>
        <tr>
          <td>Бележки</td>
          <td><textarea id="notes" name="notes" cols="40" rows="3" style="width:358px"><?=((isset($row['notes']))?HTMLFormat($row['notes']):'')?></textarea></td>
        </tr>
        <tr>
          <td>&nbsp;</td>
          <td><input type="button" id="btnSubmit" name="btnSubmit" value="Съхрани" onclick="xajax_processFirm(xajax.getFormValues('frmFirm'));DisableEnableForm(true);return false;" class="nicerButton" /></td>
        </tr>
      </table>
      <?php if($firm_id) { ?>
      <div class="divider1">&nbsp;</div>
      <table class="listing highlight" cellpadding="4" cellspacing="0" width="99%">
        <tr class="notover">
          <td colspan="4"><h3>Договори</h3></td>
          <td colspan="4" align="right"><a href="acc_contract.php?firm_id=<?=$firm_id?>">Нов договор</a></td>
        </tr>
        <tr class="notover">
          <th>No на договор</th>
          <th>No на фактура</th>
          <th>Дата на сключване</th>
          <th>Начало на договора</th>
          <th>Край на договора</th>
          <th>Дължима сума</th>
          <th>Платено</th>
          <th>&nbsp;</th>
        </tr>
        <?php
        $rows = $dbInst->query("SELECT * FROM `acc_contracts` WHERE `firm_id` = $firm_id ORDER BY `contract_start_date` DESC, `contract_id` DESC");
        if(!empty($rows)) {
        	foreach ($rows as $row) {
        ?>
        <tr>
          <td align="left"><a href="acc_contract.php?firm_id=<?=$firm_id?>&amp;contract_id=<?=$row['contract_id']?>"><?=HTMLFormat($row['contract_num'])?></a><?php if('1'==$row['contract_halt']) { ?> <img src="img/inactive.gif" border="0" alt="прекратен" width="21" height="19" align="top" /><?php } ?></td>
          <td align="center"><a href="acc_contract.php?firm_id=<?=$firm_id?>&amp;contract_id=<?=$row['contract_id']?>"><?=HTMLFormat($row['invoice_num'])?></a></td>
          <td align="center"><a href="acc_contract.php?firm_id=<?=$firm_id?>&amp;contract_id=<?=$row['contract_id']?>"><?=((!empty($row['contract_date']))?date('d.m.Y', strtotime($row['contract_date'])):'--')?></a></td>
          <td align="center"><?=((!empty($row['contract_start_date']))?date('d.m.Y', strtotime($row['contract_start_date'])):'--')?></td>
          <td align="center"><?=((!empty($row['contract_end_date']))?date('d.m.Y', strtotime($row['contract_end_date'])):'--')?></td>
          <td align="right"><?=number_format($row['amount_due_total'], 2, '.', ' ')?> лв.</td>
          <td align="right"><?=number_format($row['amount_paid_total'], 2, '.', ' ')?> лв.</td>
          <td align="right"><?php if(in_array($_SESSION['sess_access_level'], array(1))) { ?><a href="<?=basename($_SERVER['PHP_SELF'])?>?firm_id=<?=$firm_id?>&amp;del=<?=$row['contract_id']?>" onclick="if(!confirm('Наистина ли искате да изтриете договора?')){return false;}">Изтрий</a><?php } ?>&nbsp;</td>
        </tr>
        <?php
        	}
        } else {
        ?>
        <tr>
          <td colspan="8">Няма сключени договори с фирмата.</td>
        </tr>
        <?php
        }
        ?>
      </table>
      <?php } ?>
      <div class="divider1">&nbsp;</div>
      <div class="divider1">&nbsp;</div>
    </form>

<?php include("acc_footer.php"); ?>