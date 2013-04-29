<?php
require('includes.php');

// Xajax begin
require ('xajax/xajax_core/xajax.inc.php');
function processFactors($aFormValues) {
	$objResponse = new xajaxResponse();

	$objResponse->assign("btnSubmit","disabled",false);
	$objResponse->assign("btnSubmit","value","Съхрани");

	global $dbInst;
	$dbInst->setFactors($aFormValues);
	$objResponse->assign("sub1","innerHTML",echoFactors());

	$objResponse->script('$("#listtable input:text").css("width","99%")');
	$objResponse->call("stripTable","listtable");
	$objResponse->call("DisableEnableForm",false);
	return $objResponse;
}
function processDoctorPos($aFormValues) {
	$objResponse = new xajaxResponse();

	$objResponse->assign("btnSubmit","disabled",false);
	$objResponse->assign("btnSubmit","value","Съхрани");

	global $dbInst;
	$dbInst->setDoctorPos($aFormValues);
	$objResponse->assign("sub1","innerHTML",echoDoctorPos());

	$objResponse->script('$("#listtable input:text").css("width","99%")');
	$objResponse->call("stripTable","listtable");
	$objResponse->call("DisableEnableForm",false);
	return $objResponse;
}
function deleteFactor($factor_id) {
	$objResponse = new xajaxResponse();
	global $dbInst;
	if($dbInst->removeFactor($factor_id)) {
		$objResponse->call("removeLine","line_$factor_id");
	}
	return $objResponse;
}
function deleteDoctorPos($doctor_pos_id) {
	$objResponse = new xajaxResponse();
	global $dbInst;
	if($dbInst->removeDoctorPos($doctor_pos_id)) {
		$objResponse->call("removeLine","line_$doctor_pos_id");
	}
	return $objResponse;
}
function processLabs($aFormValues) {
	$objResponse = new xajaxResponse();

	$objResponse->assign("btnSubmit","disabled",false);
	$objResponse->assign("btnSubmit","value","Съхрани");

	global $dbInst;
	$dbInst->setLabs($aFormValues);
	$objResponse->assign("sub1","innerHTML",echoLabs());

	$objResponse->script('$("#listtable input:text").css("width","99%")');
	$objResponse->call("stripTable","listtable");
	$objResponse->call("DisableEnableForm",false);
	return $objResponse;
}
function deleteLab($indicator_id) {
	$objResponse = new xajaxResponse();
	global $dbInst;
	if($dbInst->removeLab($indicator_id)) {
		$objResponse->call("removeLine","line_$indicator_id");
	}
	return $objResponse;
}
function processDoctor($aFormValues) {
	$objResponse = new xajaxResponse();

	$objResponse->assign("btnDoctor","disabled",false);
	$objResponse->assign("btnDoctor","value","Съхрани");
	$objResponse->call("DisableEnableForm",false);

	if(trim($aFormValues['d_doctor_name']) == '') {
		$objResponse->alert("Моля, въведете имената на фамилния лекар.");
		return $objResponse;
	}

	global $dbInst;
	$doctor_id = $dbInst->processDoctor($aFormValues); // Insert/Update doctor
	$objResponse->assign("d_doctor_id","value",$doctor_id);
	return $objResponse;
}
function processStmInfo($aFormValues) {
	$objResponse = new xajaxResponse();

	$objResponse->assign("btnSubmit","disabled",false);
	$objResponse->assign("btnSubmit","value","Съхрани");
	$objResponse->call("DisableEnableForm",false);

	if(trim($aFormValues['stm_name']) == '') {
		$objResponse->alert("Моля, въведете наименование на СТМ.");
		return $objResponse;
	}
	if(trim($aFormValues['address']) == '') {
		$objResponse->alert("Моля, въведете адрес на СТМ.");
		return $objResponse;
	}
	if(trim($aFormValues['chief']) == '') {
		$objResponse->alert("Моля, въведете имената на управителя на СТМ.");
		return $objResponse;
	}
	if(trim($aFormValues['email']) != '' && !EMailIsCorrect($aFormValues['email'])) {
		$objResponse->alert($aFormValues['email'] . " е невалиден e-mail.");
		return $objResponse;
	}

	global $dbInst;
	$doctor_id = $dbInst->processStmInfo($aFormValues); // processStmInfo

	return $objResponse;
}
function deleteDoctor($doctor_id) {
	$objResponse = new xajaxResponse();

	global $dbInst;
	$dbInst->removeDoctor($doctor_id);
	$objResponse->script("self.parent.location.reload();");

	return $objResponse;
}
function changePwd($aFormValues) {
	$objResponse = new xajaxResponse();

	$objResponse->assign("btnSubmit","disabled",false);
	$objResponse->assign("btnSubmit","value","Актуализирай");
	$objResponse->call("DisableEnableForm",false);

	global $dbInst;


	$user_pass = trim($aFormValues['user_pass']);
	if($user_pass == '') {
		$objResponse->alert('Моля, въведената сегашната парола!');
		return $objResponse;
	}
	$query = sprintf("SELECT user_pass FROM users WHERE user_id = %d", $_SESSION['sess_user_id']);
	$pwd = $dbInst->fnSelectSingleRow($query);
	if($pwd['user_pass'] != $user_pass) {
		$objResponse->alert('Въведената сегашна парола е невалидна!');
		return $objResponse;
	}
	$new_user_pass = trim($aFormValues['new_user_pass']);
	if($new_user_pass == '') {
		$objResponse->alert('Моля, въведената новата парола!');
		return $objResponse;
	}
	$new_user_pass2 = trim($aFormValues['new_user_pass2']);
	if($new_user_pass2 == '') {
		$objResponse->alert('Моля, повторете паролата!');
		return $objResponse;
	}
	if($new_user_pass != $new_user_pass2) {
		$objResponse->alert('Паролите не са еднакви!');
		return $objResponse;
	}

	$db = $dbInst->getDBHandle();
	$query = "UPDATE users SET user_pass = '". $dbInst->checkStr($new_user_pass)."' WHERE user_id = '$_SESSION[sess_user_id]'";
	$count = $db->exec($query); //returns affected rows
	if($count) 
	$objResponse->alert('Паролата бе успешно променена!');
	else 
	$objResponse->alert('Възникна проблем при промяна на паролата!');

	return $objResponse;
}
$xajax = new xajax();
$xajax->registerFunction("processFactors");
$xajax->registerFunction("processDoctorPos");
$xajax->registerFunction("deleteFactor");
$xajax->registerFunction("deleteDoctorPos");
$xajax->registerFunction("processLabs");
$xajax->registerFunction("deleteLab");
$xajax->registerFunction("processDoctor");
$xajax->registerFunction("processStmInfo");
$xajax->registerFunction("deleteDoctor");
$xajax->registerFunction("changePwd");
//$xajax->setFlag("debug",true);
$echoJS = $xajax->getJavascript('xajax/');
$xajax->processRequest();
// Xajax end

function echoFactors() {
	global $dbInst;
	ob_start();
	?>
          <table id="listtable">
            <tbody>
              <tr>
                <th>Фактор</th>
                <th>ПДК max</th>
                <th>ПДК min</th>
                <th>Мерни единици</th>
                <th>&nbsp;</th>
              </tr>
              <?php
              $factors = $dbInst->getFactors();
              foreach ($factors as $factor) {
              ?>
              <tr id="line_<?=$factor['factor_id']?>">
                <td><input type="text" id="factor_name_<?=$factor['factor_id']?>" name="factor_name_<?=$factor['factor_id']?>" value="<?=$factor["factor_name"]?>" size="40" maxlength="60" /></td>
                <td><input type="text" id="pdk_max_<?=$factor['factor_id']?>" name="pdk_max_<?=$factor['factor_id']?>" value="<?=(($factor["pdk_max"])?$factor["pdk_max"]:'')?>" size="15" maxlength="50" onKeyPress="return floatsonly(this, event);" /></td>
                <td><input type="text" id="pdk_min_<?=$factor['factor_id']?>" name="pdk_min_<?=$factor['factor_id']?>" value="<?=(($factor["pdk_min"])?$factor["pdk_min"]:'')?>" size="15" maxlength="50" onKeyPress="return floatsonly(this, event);" /></td>
                <td><input type="text" id="factor_dimension_<?=$factor['factor_id']?>" name="factor_dimension_<?=$factor['factor_id']?>" value="<?=$factor["factor_dimension"]?>" size="20" maxlength="20" /></td>
                <td align="center"><a href="javascript:void(null);" onclick="xajax_deleteFactor(<?=$factor['factor_id']?>);return false;" title="Изтриване"><img src="img/delete.gif" alt="delete" width="15" height="15" border="0" align="top" /></a></td>
              </tr>              
              <?php
              }
              ?>
              <tr>
                <td><input type="text" id="factor_name_0" name="factor_name_0" value="" size="40" maxlength="50" class="newItem" /></td>
                <td><input type="text" id="pdk_max_0" name="pdk_max_0" value="" size="15" maxlength="50" class="newItem" onKeyPress="return floatsonly(this, event);" /></td>
                <td><input type="text" id="pdk_min_0" name="pdk_min_0" value="" size="15" maxlength="50" class="newItem" onKeyPress="return floatsonly(this, event);" /></td>
                <td><input type="text" id="factor_dimension_0" name="factor_dimension_0" value="" size="20" maxlength="50" class="newItem" /></td>
                <td align="center">&nbsp;</td>
              </tr>
              <tr>
                <th colspan="5" align="center"><input type="button" id="btnSubmit" name="btnSubmit" value="Съхрани" class="nicerButtons" onclick="this.disabled=true;this.value='обработка...';xajax_processFactors(xajax.getFormValues('frmSubmit'));DisableEnableForm(true);return false;" /></th>
              </tr>
            </tbody>
          </table>
	<?php
	$buff = ob_get_contents();
	ob_end_clean();
	return $buff;
}

function echoDoctorPos() {
	global $dbInst;
	ob_start();
	?>
          <table id="listtable">
            <tbody>
              <tr>
                <th>Специалности</th>
                <th>&nbsp;</th>
              </tr>
              <?php
              $rows = $dbInst->getDoctorsPulldown('doctor_pos_id');
              foreach ($rows as $row) {
              ?>
              <tr id="line_<?=$row['doctor_pos_id']?>">
                <td><input type="text" id="doctor_pos_name_<?=$row['doctor_pos_id']?>" name="doctor_pos_name_<?=$row['doctor_pos_id']?>" value="<?=HTMLFormat($row["doctor_pos_name"])?>" size="40" maxlength="60" /></td>
                <td align="center"><a href="javascript:void(null);" onclick="xajax_deleteDoctorPos(<?=$row['doctor_pos_id']?>);return false;" title="Изтриване"><img src="img/delete.gif" alt="delete" width="15" height="15" border="0" align="top" /></a></td>
              </tr>              
              <?php
              }
              ?>
              <tr>
                <td><input type="text" id="doctor_pos_name_0" name="doctor_pos_name_0" value="" size="40" maxlength="50" class="newItem" /></td>
                <td align="center">&nbsp;</td>
              </tr>
              <tr>
                <th colspan="5" align="center"><input type="button" id="btnSubmit" name="btnSubmit" value="Съхрани" class="nicerButtons" onclick="this.disabled=true;this.value='обработка...';xajax_processDoctorPos(xajax.getFormValues('frmSubmit'));DisableEnableForm(true);return false;" /></th>
              </tr>
            </tbody>
          </table>
	<?php
	$buff = ob_get_contents();
	ob_end_clean();
	return $buff;
}

function echoSTM() {
	global $dbInst;
	ob_start();
	$f = $dbInst->getStmInfo();
	?>
          <table border="0" cellpadding="0" cellspacing="0" id="grayTable">
            <tr>
              <th colspan="4"><p>Основна информация за службата по трудова медицина </p></th>
            </tr>
            <tr>
              <td><p><strong>Наименование: </strong></p></td>
              <td><p><?=((isset($f['stm_name']))?'<strong>'.HTMLFormat($f['stm_name']).'</strong>':'')?>
                  <input type="hidden" id="stm_name" name="stm_name" value="<?=((isset($f['stm_name']))?HTMLFormat($f['stm_name']):'')?>" />
                </p></td>
            </tr>
            <tr>
              <td><p>Удостоверение  №: </p></td>
              <td><p><?=((isset($f['license_num']))?'<strong>'.HTMLFormat($f['license_num']).'</strong>':'')?>
                  <input type="hidden" id="license_num" name="license_num" value="<?=((isset($f['license_num']))?HTMLFormat($f['license_num']):'')?>" /> от Министерство на Здравеопазването
                </p></td>
            </tr>
            <tr>
              <td><p><strong>Адрес: </strong></p></td>
              <td><p>
                  <input type="text" id="address" name="address" value="<?=((isset($f['address']))?HTMLFormat($f['address']):'')?>" size="80" maxlength="100" />
                </p></td>
            </tr>
            <tr>
              <td nowrap="nowrap"><p><strong>Управител: </strong></p></td>
              <td><p>
                  <input type="text" id="chief" name="chief" value="<?=((isset($f['chief']))?HTMLFormat($f['chief']):'')?>" size="80" maxlength="100" />
                </p></td>
            </tr>
            <tr>
              <td nowrap="nowrap"><p>Тел. 1: </p></td>
              <td><p>
                  <input type="text" id="phone1" name="phone1" value="<?=((isset($f['phone1']))?HTMLFormat($f['phone1']):'')?>" size="40" maxlength="50" />
                </p></td>
            </tr>
            <tr>
              <td nowrap="nowrap"><p>Тел. 2: </p></td>
              <td><p>
                  <input type="text" id="phone2" name="phone2" value="<?=((isset($f['phone2']))?HTMLFormat($f['phone2']):'')?>" size="40" maxlength="50" />
                </p></td>
            </tr>
            <tr>
              <td nowrap="nowrap"><p>Факс: </p></td>
              <td><p>
                  <input type="text" id="fax" name="fax" value="<?=((isset($f['fax']))?HTMLFormat($f['fax']):'')?>" size="40" maxlength="50" />
                </p></td>
            </tr>
            <tr>
              <td nowrap="nowrap"><p>E-mail: </p></td>
              <td><p><input type="text" id="email" name="email" value="<?=((isset($f['email']))?HTMLFormat($f['email']):'')?>" size="40" maxlength="50" /></p></td>
            </tr>
            <tr>
              <th colspan="4" align="center"><input type="button" id="btnSubmit" name="btnSubmit" value="Съхрани" class="nicerButtons" onclick="this.disabled=true;this.value='обработка...';xajax_processStmInfo(xajax.getFormValues('frmSubmit'));DisableEnableForm(true);return false;" /></th>
            </tr>
          </table>
	<?php
	$buff = ob_get_contents();
	ob_end_clean();
	return $buff;
}

function echoUpd() {
	global $dbInst;
	global $msg;
	ob_start();
	$f = $dbInst->getStmInfo();

	if(isset($_SESSION['sess_msg'])) {
		$msg = $_SESSION['sess_msg'];
		unset($_SESSION['sess_msg']);
	}
	if(count($msg)) {
		echo '<p class="notes">';
		echo implode('<br />', $msg);
		echo '<p>';
	}
	?>
          <table border="0" cellpadding="0" cellspacing="0" id="grayTable">
            <tr>
              <th><p>Избери файл с данни: <input type="file" id="updfile" name="updfile" value="" /></p></th>
            </tr>
            <tr>
              <th align="center"><input type="submit" id="btnUpd" name="btnUpd" value="Актуализирай" class="nicerButtons" onclick="this.value='обработка...';" /></th>
            </tr>
          </table>
	<?php
	$buff = ob_get_contents();
	ob_end_clean();
	return $buff;
}

function echoPwd() {
	global $dbInst;
	ob_start();
	?>
          <table border="0" cellpadding="0" cellspacing="0" id="grayTable">
            <tr>
              <th align="left"><p>Сегашна парола </p></th><th><p><input type="text" id="user_pass" name="user_pass" value="" /></p></th>
            </tr>
            <tr>
              <th align="left"><p>Нова парола </p></th><th><p><input type="text" id="new_user_pass" name="new_user_pass" value="" /></p></th>
            </tr>
            <tr>
              <th align="left"><p>Повторете паролата </p></th><th><p><input type="text" id="new_user_pass2" name="new_user_pass2" value="" /></p></th>
            </tr>
            <tr>
              <th align="center" colspan="2"><p><input type="button" id="btnSubmit" name="btnSubmit" value="Актуализирай" class="nicerButtons" onclick="this.disabled=true;this.value='обработка...';xajax_changePwd(xajax.getFormValues('frmSubmit'));DisableEnableForm(true);return false;" /></p></th>
            </tr>
          </table>
	<?php
	$buff = ob_get_contents();
	ob_end_clean();
	return $buff;
}

function echoLabs() {
	global $dbInst;
	ob_start();
	?>
          <table id="listtable">
            <tbody>
              <tr>
                <th>Вид</th>
                <th>Показател</th>
                <th>Min</th>
                <th>Max</th>
                <th>Мерни единици</th>
                <th>&nbsp;</th>
              </tr>
              <?php
              $labs = $dbInst->getLabs();
              foreach ($labs as $lab) {
              ?>
              <tr id="line_<?=$lab['indicator_id']?>">
                <td><input type="text" id="indicator_type_<?=$lab['indicator_id']?>" name="indicator_type_<?=$lab['indicator_id']?>" value="<?=$lab["indicator_type"]?>" size="40" maxlength="60" /></td>
                <td><input type="text" id="indicator_name_<?=$lab['indicator_id']?>" name="indicator_name_<?=$lab['indicator_id']?>" value="<?=$lab["indicator_name"]?>" size="40" maxlength="60" /></td>                
                <td><input type="text" id="pdk_min_<?=$lab['indicator_id']?>" name="pdk_min_<?=$lab['indicator_id']?>" value="<?=(($lab["pdk_min"])?$lab["pdk_min"]:'')?>" size="15" maxlength="50" onKeyPress="return floatsonly(this, event);" /></td>
                <td><input type="text" id="pdk_max_<?=$lab['indicator_id']?>" name="pdk_max_<?=$lab['indicator_id']?>" value="<?=(($lab["pdk_max"])?$lab["pdk_max"]:'')?>" size="15" maxlength="50" onKeyPress="return floatsonly(this, event);" /></td>
                <td><input type="text" id="indicator_dimension_<?=$lab['indicator_id']?>" name="indicator_dimension_<?=$lab['indicator_id']?>" value="<?=$lab["indicator_dimension"]?>" size="20" maxlength="20" /></td>
                <td align="center"><a href="javascript:void(null);" onclick="xajax_deleteLab(<?=$lab['indicator_id']?>);return false;" title="Изтриване"><img src="img/delete.gif" alt="delete" width="15" height="15" border="0" align="top" /></a></td>
              </tr>              
              <?php
              }
              ?>
              <tr>
                <td><input type="text" id="indicator_type_0" name="indicator_type_0" value="" size="40" maxlength="50" class="newItem" /></td>
                <td><input type="text" id="indicator_name_0" name="indicator_name_0" value="" size="40" maxlength="50" class="newItem" /></td>
                <td><input type="text" id="pdk_min_0" name="pdk_min_0" value="" size="15" maxlength="50" class="newItem" onKeyPress="return floatsonly(this, event);" /></td>
                <td><input type="text" id="pdk_max_0" name="pdk_max_0" value="" size="15" maxlength="50" class="newItem" onKeyPress="return floatsonly(this, event);" /></td>
                <td><input type="text" id="indicator_dimension_0" name="indicator_dimension_0" value="" size="20" maxlength="50" class="newItem" /></td>
                <td align="center">&nbsp;</td>
              </tr>
              <tr>
                <th colspan="6" align="center"><input type="button" id="btnSubmit" name="btnSubmit" value="Съхрани" class="nicerButtons" onclick="this.disabled=true;this.value='обработка...';xajax_processLabs(xajax.getFormValues('frmSubmit'));DisableEnableForm(true);return false;" /></th>
              </tr>
            </tbody>
          </table>
	<?php
	$buff = ob_get_contents();
	ob_end_clean();
	return $buff;
}

function echoDoctors() {
	global $dbInst;

	$perPage = (isset($_GET['perPage'])) ? abs(intval($_GET['perPage'])) : 25;
	$_SESSION['sess_QUERY_STRING'] = (isset($_SERVER['QUERY_STRING'])) ? '?'.$_SERVER['QUERY_STRING'] : '';

	// PAGER BEGIN
	require_once 'Pager/Pager_Wrapper.php';
	$pagerOptions = array(
	'mode'    => 'Jumping',			// Sliding
	'delta'   => 10,				// 2
	'perPage' => $perPage,
	//'separator'=>'|',
	'spacesBeforeSeparator'=>1,	// number of spaces before the separator
	'spacesAfterSeparator'=>1,		// number of spaces after the separator
	//'linkClass'=>'', 				// name of CSS class used for link styling
	//'curPageLinkClassName'=>'',	// name of CSS class used for current page link
	'urlVar' =>'page',				// name of pageNumber URL var, for example "pageID"
	//'path'=>SECURE_URL,				// complete path to the page (without the page name)
	'firstPagePre'=>'',				// string used before first page number
	'firstPageText'=>'FIRST',		// string used in place of first page number
	'firstPagePost'=>'',			// string used after first page number
	'lastPagePre'=>'',				// string used before last page number
	'lastPageText'=>'LAST',			// string used in place of last page number
	'lastPagePost'=>'',				// string used after last page number
	'curPageLinkClassName'=>'current',
	'prevImg'=>'<img src="img/pg-prev.gif" alt="prev" width="16" height="16" border="0" align="texttop" />',
	'nextImg'=>'<img src="img/pg-next.gif" alt="next" width="16" height="16" border="0" align="texttop" />',
	'clearIfVoid'=>true				// if there's only one page, don't display pager
	);
	$query = "SELECT d.*, (SELECT COUNT(*) FROM workers w WHERE w.doctor_id = d.doctor_id ) AS patients_num FROM doctors d";
	$txtCondition = "";

	if(isset($_GET['btnFind']) || (isset($_GET['keyword']) && trim($_GET['keyword']) != '') ) {	// Filter workers
		if(isset($_GET['keyword']) && trim($_GET['keyword']) != '') {
			$keyword = $dbInst->checkStr($_GET['keyword']);
			$uc_keyword = $dbInst->my_mb_ucfirst($keyword);
			$txtCondition .= (preg_match('/\bWHERE\b/', $txtCondition)) ? ' AND ' : ' WHERE ';
			$txtCondition .= "(doctor_name LIKE '%$keyword%' OR address LIKE '%$keyword%' OR doctor_name LIKE '%$uc_keyword%' OR address LIKE '%$uc_keyword%')";
		}
	}	// Search end
	$sortArr = array('doctor_name','address','phone1','phone2','patients_num');
	if (isset($_GET["sort_by"]) && in_array($_GET["sort_by"],$sortArr)) {
		$order = (isset($_GET['order']) && $_GET['order']=='ASC') ? 'ASC' : 'DESC';
		$txtCondition .= " ORDER BY `$_GET[sort_by]` $order, d.doctor_id";
	}
	else $txtCondition .= " ORDER BY d.doctor_name, d.doctor_id";

	$query .= $txtCondition;
	//die($query);
	$db = $dbInst->getDBHandle();
	$paged_data = Pager_Wrapper_PDO($db, $query, $pagerOptions);
	$doctors = $paged_data['data'];  //paged data
	$links = $paged_data['links']; //xhtml links for page navigation
	$current = (isset($paged_data['page_numbers']['current'])) ? $paged_data['page_numbers']['current'] : 0;
	$totalItems = $paged_data['totalItems'];
	$from = ($current) ? $paged_data['from'] : 0;
	$to = $paged_data['to'];
	// PAGER END


	ob_start();
	?>
          <div id="searchHolder">
            <input type="hidden" id="page" name="page" value="1" />
            <input type="hidden" id="tab" name="tab" value="doctors" />
            <table width="100%" border="0" cellpadding="3" cellspacing="0" id="admin_search" class="inset">
              <tbody>
                <tr>
                  <td>Търсене по име или адрес на практика:
                    <input type="text" id="keyword" name="keyword" value="<?=((isset($_GET['keyword']))?HTMLFormat($_GET['keyword']):'')?>" size="35" />
                    <input type="button" id="btnFind" name="btnFind" value="Намери" class="nicerButtons" onclick="window.location='<?=basename($_SERVER['PHP_SELF'])?>?tab=doctors&btnFind=go&keyword='+document.getElementById('keyword').value" /></td>
                </tr>
              </tbody>
            </table>
          </div>
          <div id="actionsdiv">
            <table width="100%" border="0">
              <tr>
                <td align="right">Резултати <?=$from?> - <?=$to?> от <?=$totalItems?><?php if($paged_data['links']) { ?> / Иди на страница <?=$paged_data['links']?><?php } ?></td>
              </tr>
            </table>
          </div>
          <table id="listtable">
            <tbody>
              <tr>
                <th><?php if (isset($_GET["sort_by"])&&$_GET["sort_by"]=="doctor_name"){?><img src="img/<?php if (isset($_GET["order"])&&$_GET["order"]=="DESC"){ ?>sort_arrow_down.gif<?php } else { ?>sort_arrow_up.gif<?php } ?>" alt="Sort" width="16" height="16" border="0" /><?php } ?>
                <a href="<?=basename($_SERVER['PHP_SELF']).cleanQueryString('sort_by=doctor_name&order='.((isset($_GET["sort_by"])&&$_GET["sort_by"]=="doctor_name")?(($_GET["order"]=="DESC")?"ASC":"DESC"):"ASC"))?>" title="Сортиране по име">Име</a></th>
                <th><?php if (isset($_GET["sort_by"])&&$_GET["sort_by"]=="address"){?><img src="img/<?php if (isset($_GET["order"])&&$_GET["order"]=="DESC"){ ?>sort_arrow_down.gif<?php } else { ?>sort_arrow_up.gif<?php } ?>" alt="Sort" width="16" height="16" border="0" /><?php } ?>
                <a href="<?=basename($_SERVER['PHP_SELF']).cleanQueryString('sort_by=address&order='.((isset($_GET["sort_by"])&&$_GET["sort_by"]=="address")?(($_GET["order"]=="DESC")?"ASC":"DESC"):"ASC"))?>" title="Сортиране по адрес">Адрес на практика</a></th>
                <th><?php if (isset($_GET["sort_by"])&&$_GET["sort_by"]=="phone1"){?><img src="img/<?php if (isset($_GET["order"])&&$_GET["order"]=="DESC"){ ?>sort_arrow_down.gif<?php } else { ?>sort_arrow_up.gif<?php } ?>" alt="Sort" width="16" height="16" border="0" /><?php } ?>
                <a href="<?=basename($_SERVER['PHP_SELF']).cleanQueryString('sort_by=phone1&order='.((isset($_GET["sort_by"])&&$_GET["sort_by"]=="phone1")?(($_GET["order"]=="DESC")?"ASC":"DESC"):"ASC"))?>" title="Сортиране по тел. 1">Тел. 1</a></th>
                <th><?php if (isset($_GET["sort_by"])&&$_GET["sort_by"]=="phone2"){?><img src="img/<?php if (isset($_GET["order"])&&$_GET["order"]=="DESC"){ ?>sort_arrow_down.gif<?php } else { ?>sort_arrow_up.gif<?php } ?>" alt="Sort" width="16" height="16" border="0" /><?php } ?>
                <a href="<?=basename($_SERVER['PHP_SELF']).cleanQueryString('sort_by=phone2&order='.((isset($_GET["sort_by"])&&$_GET["sort_by"]=="phone2")?(($_GET["order"]=="DESC")?"ASC":"DESC"):"ASC"))?>" title="Сортиране по тел. 2">Тел. 2</a></th>
                <th><?php if (isset($_GET["sort_by"])&&$_GET["sort_by"]=="patients_num"){?><img src="img/<?php if (isset($_GET["order"])&&$_GET["order"]=="DESC"){ ?>sort_arrow_down.gif<?php } else { ?>sort_arrow_up.gif<?php } ?>" alt="Sort" width="16" height="16" border="0" /><?php } ?>
                <a href="<?=basename($_SERVER['PHP_SELF']).cleanQueryString('sort_by=patients_num&order='.((isset($_GET["sort_by"])&&$_GET["sort_by"]=="patients_num")?(($_GET["order"]=="DESC")?"ASC":"DESC"):"ASC"))?>" title="Сортиране по тел. 2">Бр. пациенти</a></th>
                <th>Редактирай</th>
                <th>Изтрий</th>
              </tr>
              <?php
              if(is_array($doctors) && count($doctors)>0) {
              	$i=0;
              	foreach ($doctors as $row) {
              ?>
              <tr>
                <td><?=$row['doctor_name']?></td>
                <td><?=$row['address']?></td>
                <td><?=$row['phone1']?></td>
                <td><?=$row['phone2']?></td>
                <td align="center"><strong><?=$row['patients_num']?></strong></td>
                <td align="center"><a href="form_doctor.php?doctor_id=<?=$row['doctor_id']?>&amp;reload=1&amp;<?=SESS_NAME.'='.session_id()?>&amp;height=160&amp;width=472&amp;modal=true" title="Редактиране на данните на <?=HTMLFormat($row['doctor_name'])?>" class="thickbox"><img src="img/edititem.gif" alt="Редактиране данните на <?=HTMLFormat($row['doctor_name'])?>" width="16" height="16" border="0" /></a></td>
                <td align="center"><a href="javascript:void(null);" onclick="var answ=confirm('Наистина ли искате да изтриете данните за фамилния лекар?');if(answ){xajax_deleteDoctor(<?=$row['doctor_id']?>);}return false;" title="Изтриване данните на <?=HTMLFormat($row['doctor_name'])?>"><img src="img/delete.gif" alt="Изтриване данните на <?=HTMLFormat($row['doctor_name'])?>" width="15" height="15" border="0" /></a></td>
              </tr>
              <?php
              	}
              } else {
              ?>
              <tr>
                <td colspan="8">Няма намерени резултати.</td>
              </tr>
              <?php } ?>
              <tr class="notover">
                <td colspan="8">&nbsp;</td>
              </tr>
              <!--<tr class="notover">
                <td colspan="7"><strong>Покажи </strong><input type="text" id="perPage" name="perPage" value="<?=$perPage?>" size="5" maxlength="10" onKeyPress="return numbersonly(this, event);" /> <strong>работещи на страница</strong></td>
              </tr>-->
              <tr>
                <th colspan="7" align="center"><input type="button" id="btnSubmit" name="btnSubmit" value="Нов лекар" onclick="tb_show('Добавяне на нов фамилен лекар','form_doctor.php?doctor_id=0&amp;reload=1&amp;<?=SESS_NAME.'='.session_id()?>&amp;height=160&amp;width=472&amp;modal=true',0);return false;" class="nicerButtons" /></th>
              </tr>
            </tbody>
          </table>
          <div id="actionsdiv">
            <table width="100%" border="0">
              <tr>
                <td align="right">Резултати <?=$from?> - <?=$to?> от <?=$totalItems?><?php if($paged_data['links']) { ?> / Иди на страница <?=$paged_data['links']?><?php } ?></td>
              </tr>
            </table>
          </div>
	<?php
	$buff = ob_get_contents();
	ob_end_clean();
	return $buff;
}

$tab = (isset($_GET['tab'])) ? $_GET['tab'] : 'env';

$msg = array();
// Update the application
if(isset($_POST['btnUpd']))
{
	//if ( $_FILES["updfile"]['size'] != 0 && $_FILES["updfile"]['size'] < 1048576 )
	if ( $_FILES["updfile"]['size'] != 0 && $_FILES["updfile"]['size'] < 1048576 )
	{
		//Allowable file Mime Types. Add more mime types if you want
		$FILE_MIMES = array('text/xml');
		//Allowable file ext. names. you may add more extension names.
		$FILE_EXTS = array('xml');

		$fname = $_FILES["updfile"]['name'];
		$ftmp_name = $_FILES["updfile"]['tmp_name'];
		$ftype = $_FILES["updfile"]['type'];
		$fsize = $_FILES["updfile"]['size'];
		$fext = strtolower(substr($fname,-3));

		$file_name  = time() . "_";
		$file_name .= str_replace( " ", "_", $fname );
		$file_name  = strtolower( $file_name );

		if (in_array($ftype, $FILE_MIMES) || in_array($fext, $FILE_EXTS))
		{
			// FILE TYPE IS ALLOWED
			if (move_uploaded_file($ftmp_name, $file_name))
			{
				$db = $dbInst->getDBHandle();
				$xml = file_get_contents($file_name);
				$i = 0;
				if(preg_match_all('/\<query\>(.*?)\<\/query\>/si', $xml, $queries))
				{
					foreach ($queries[1] as $query) {
						$count = $db->exec(trim($query)); //returns affected rows
						$i++;
					}
				}

				if($i) $msg[] = 'Данните в системата бяха успешно актуализирани.';
				else $msg[] = 'Не бяха извършени актуализации в системата.';

				if(file_exists($file_name)) @unlink($file_name);
			}
			else
			{
				$msg[] = "Possible fishy upload! Here's some debugging info:<br>";
				$msg[] = $_FILES["updfile"]['error'] . " | " . $file_name;
			}
		}
		else
		{
			$msg[] = "Can't upload this type of files: " . $ftype . ", (" . $fname . ")<br>";
		}

		$_SESSION['sess_msg'] = $msg;
		header('Location:'.basename($_SERVER['PHP_SELF']).'?tab=upd');
		exit();
	}
}

include("header.php");
?>
	<script type="text/javascript">
	//<![CDATA[
	$(document).ready(function() {
		stripTable('listtable');
		$("#listtable input:text").css("width","99%");
	});
	//]]>
	</script>
    <ul class="propDetailNav">
      <li<?=(($tab=='env')?' class="active"':'')?>><a href="official_data.php?tab=env">Фактори на работната среда</a></li>
      <li<?=(($tab=='lab')?' class="active"':'')?>><a href="official_data.php?tab=lab">Лабораторни показатели</a></li>
      <li<?=(($tab=='doctors')?' class="active"':'')?>><a href="official_data.php?tab=doctors">Фамилни лекари</a></li>
      <li<?=(($tab=='stm')?' class="active"':'')?>><a href="official_data.php?tab=stm">За СТМ</a></li>
      <li style="background-color:#FF0000"<?=(($tab=='pwd')?' class="active"':'')?>><a href="official_data.php?tab=pwd">Смяна на парола</a></li>
      <li style="background-color:#FF0000"<?=(($tab=='upd')?' class="active"':'')?>><a href="official_data.php?tab=upd">Актуализация на системата</a></li>
      <li class="clear">&nbsp;</li>
    </ul>
    <div id="contentinner">
      <?php if($tab == 'doctors') { ?>
      <form id="frmSubmit" name="frmSubmit" action="<?=basename($_SERVER['PHP_SELF'])?>" method="get">
      <?php } elseif ($tab == 'upd') { ?>
      <form id="frmSubmit" name="frmSubmit" action="<?=basename($_SERVER['PHP_SELF'])?>?tab=<?=$tab?>" method="post" enctype="multipart/form-data">
      <?php } else { ?>
      <form id="frmSubmit" name="frmSubmit" action="javascript:void(null);">
      <?php } ?>
        <div id="sub1" class="submenu">
		<?php
		switch ($tab) {
			case 'lab':
				echo echoLabs();
				break;

				/*case 'doctor_pos':
				echo echoDoctorPos();
				break;*/

			case 'doctors':
				echo echoDoctors();
				break;

			case 'stm':
				echo echoSTM();
				break;

			case 'upd':
				echo echoUpd();
				break;

			case 'pwd':
				echo echoPwd();
				break;

			case 'env':
			default:
				echo echoFactors();
				break;
		}
		?>
        </div>
      </form>
    </div>
    
<?php include("footer.php"); ?> 