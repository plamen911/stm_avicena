<?php
require('includes.php');

$firm_id = (isset($_GET['firm_id']) && is_numeric($_GET['firm_id'])) ? intval($_GET['firm_id']) : 0;
$firmInfo = $dbInst->getFirmInfo($firm_id);
if(!$firmInfo) {
	die('Липсва индентификатор на фирмата!');
}
$worker_id = (isset($_GET['worker_id']) && is_numeric($_GET['worker_id'])) ? intval($_GET['worker_id']) : 0;

if(isset($_POST['ajax_load_work_positions'])) {
	$out = array();
	switch ($_POST['ajax_load_work_positions']) {
		case 'work_places':
			$subdivision_id = (isset($_POST['id'])) ? intval($_POST['id']) : 0;
			$sql = "SELECT w.wplace_id AS wplace_id , w.wplace_name AS wplace_name
					FROM firm_struct_map m
					LEFT JOIN work_places w ON ( w.wplace_id = m.wplace_id )
					WHERE m.firm_id = $firm_id
					AND m.subdivision_id = $subdivision_id
					ORDER BY w.wplace_name, w.wplace_id";
			$flds = $dbInst->query($sql);
			if(!empty($flds)) {
				foreach ($flds as $fld) {
					$out[] = array('key' => $fld['wplace_id'], 'value' => stripslashes($fld['wplace_name']));
				}
			}
			break;

		case 'firm_positions':
			$wplace_id = (isset($_POST['id'])) ? intval($_POST['id']) : 0;
			$sql = "SELECT p.position_id AS position_id , p.position_name AS position_name
					FROM firm_struct_map m
					LEFT JOIN firm_positions p ON ( p.position_id = m.position_id )
					WHERE m.firm_id = $firm_id
					AND m.wplace_id = $wplace_id
					ORDER BY p.position_name, p.position_id";
			$flds = $dbInst->query($sql);
			if(!empty($flds)) {
				foreach ($flds as $fld) {
					$out[] = array('key' => $fld['position_id'], 'value' => stripslashes($fld['position_name']));
				}
			}
			break;
	}
	die(json_encode($out));
}

// Xajax begin
require ('xajax/xajax_core/xajax.inc.php');
function processWorker($aFormValues) {
	$objResponse = new xajaxResponse();

	$objResponse->assign("btnSubmit","disabled",false);
	$objResponse->assign("btnSubmit","value","Съхрани");
	$objResponse->call("DisableEnableForm",false);

	global $dbInst;
	global $firm_id;

	if(trim($aFormValues['fname']) == '' && trim($aFormValues['lname']) == '') {
		$objResponse->alert("Моля, въведете име или фамилия на работещия.");
		return $objResponse;
	}
	if('' == trim($aFormValues['egn'])/* || 10 != strlen(trim($aFormValues['egn']))*/) {
		$objResponse->alert('Моля, въведете ЕГН на работещия.');
		return $objResponse;
	}
	// Check Personal number processWorker
	$worker_id = intval($aFormValues['worker_id']);
	if(!$worker_id) {// New worker
		$query = sprintf("	SELECT w.fname, w.sname, w.lname, f.name AS firm_name
							FROM workers w
							LEFT JOIN firms f ON (f.firm_id = w.firm_id)
							WHERE f.firm_id = %d
							AND w.date_retired = ''
							AND w.egn = '%s'
							ORDER BY w.fname, w.sname, w.lname", $firm_id, $dbInst->checkStr($aFormValues['egn']));
	} else {
		$query = sprintf("	SELECT w.fname, w.sname, w.lname, f.name AS firm_name
							FROM workers w
							LEFT JOIN firms f ON (f.firm_id = w.firm_id)
							WHERE f.firm_id = %d
							AND w.date_retired = ''
							AND w.egn = '%s' 
							AND w.worker_id != %d
							ORDER BY w.fname, w.sname, w.lname", $firm_id, $dbInst->checkStr($aFormValues['egn']), $worker_id);
	}
	$rows = $dbInst->fnSelectRows($query);
	if(count($rows)) {
		$lines = array();
		$i = 1;
		foreach ($rows as $row) {
			$lines[] = $i.'). '.$row['fname'].' '.$row['sname'].' '.$row['lname'].' ('.$row['firm_name'].')';
			$i++;
		}
		$msg = "Въведеният ЕГН ($aFormValues[egn]) съвпада с ЕГН на:\n";
		$msg .= implode("\n", $lines);
		$objResponse->alert($msg);
		return $objResponse;
	}

	if(!intval($aFormValues['location_id']) && trim($aFormValues['location_name']) == '') {
		$objResponse->assign("location_name","value","");
		$objResponse->assign("location_id","value",0);
	}

	$d = new ParseBGDate();
	if( trim($aFormValues['date_curr_position_start']) != '' && !$d->Parse(trim($aFormValues['date_curr_position_start'])) ) {
		$objResponse->alert(trim($aFormValues['date_curr_position_start']).' е невалидна дата!');
		return $objResponse;
	}
	if( trim($aFormValues['date_career_start']) != '' && !$d->Parse(trim($aFormValues['date_career_start'])) ) {
		$objResponse->alert(trim($aFormValues['date_career_start']).' е невалидна дата!');
		return $objResponse;
	}
	// Check dates: Compare `date_curr_position_start` and `date_career_start` dates
	if( trim($aFormValues['date_curr_position_start']) != '' && trim($aFormValues['date_career_start']) != '' ) {
		$d->Parse(trim($aFormValues['date_curr_position_start']));
		$date_curr_position_start = mktime(0, 0, 0, $d->getMonth(), $d->getDay(), $d->getYear());
		$d->Parse(trim($aFormValues['date_career_start']));
		$date_career_start = mktime(0, 0, 0, $d->getMonth(), $d->getDay(), $d->getYear());
		if($date_career_start > $date_curr_position_start) {
			$objResponse->alert('Датата на постъпване на настоящата длъжност не може да бъде преди датата на трудовия стаж.');
			return $objResponse;
		}
	}

	$worker_id = $dbInst->processWorker($aFormValues); // Insert worker
	$objResponse->assign("worker_id","value",$worker_id);
	$objResponse->assign('lastModified','innerHTML',$dbInst->getModifiedBy('workers', 'worker_id', $worker_id));
	//$objResponse->alert("Данните за работещия бяха успешно въведени!");
	return $objResponse;
}
function processProRoute($aFormValues) {
	$objResponse = new xajaxResponse();

	$objResponse->assign("btnSubmit","disabled",false);
	$objResponse->assign("btnSubmit","value","Съхрани");
	$objResponse->call("DisableEnableForm",false);

	$worker_id = intval($aFormValues['worker_id']);
	if(!$worker_id) {
		$objResponse->alert("Моля, въведете данни за работещия.");
		$objResponse->script("window.location.href='".basename($_SERVER['PHP_SELF'])."?worker_id=".intval($aFormValues['worker_id'])."&firm_id=".intval($aFormValues['firm_id'])."&tab=worker_data';");
		return $objResponse;
	}

	global $dbInst;
	// Global check
	foreach ($aFormValues as $key=>$val) {
		if(preg_match('/^firm_name_(\d+)$/', $key, $matches)) {
			$route_id = $matches[1];
			$firm_name = $dbInst->checkStr($aFormValues['firm_name_'.$route_id]);
			$position = $dbInst->checkStr($aFormValues['position_'.$route_id]);
			$exp_length_y = $dbInst->checkStr($aFormValues['exp_length_y_'.$route_id]);
			$exp_length_m = $dbInst->checkStr($aFormValues['exp_length_m_'.$route_id]);
			/*if($route_id && $position == '') {
			$objResponse->alert("Моля, въведете всички данни за професионалния стаж на работещия.");
			return $objResponse;
			}
			else*/if (intval($exp_length_m) < 0 || intval($exp_length_m) > 12) {
			$objResponse->alert($exp_length_m." е невалиден месец!");
			return $objResponse;
			}
			/*elseif ($route_id && (!$exp_length_y && !$exp_length_m)) {
			$objResponse->alert("Моля, въведете продължителност на стажа.");
			return $objResponse;
			}*/
		}
	}
	$dbInst->processProRoute($aFormValues); // add/update worker's professional route
	$objResponse->assign("panel","innerHTML",echoProRoute($worker_id));
	$objResponse->assign('lastModified','innerHTML',$dbInst->getModifiedBy('workers', 'worker_id', $worker_id));
	return $objResponse;
}
function processDoctor($aFormValues) {
	$objResponse = new xajaxResponse();

	$objResponse->assign("btnDoctor","disabled",false);
	$objResponse->assign("btnDoctor","value","Добави");
	$objResponse->call("DisableEnableForm",false);

	if(trim($aFormValues['d_doctor_name']) == '') {
		$objResponse->alert("Моля, въведете имената на фамилния лекар.");
		return $objResponse;
	}

	global $dbInst;
	$doctor_id = $dbInst->processDoctor($aFormValues); // Insert doctor
	$objResponse->loadcommands(loadPulldown($doctor_id));
	$objResponse->script("tb_remove();");

	return $objResponse;
}
function currentServiceLength($date_curr_position_start) {
	$objResponse = new xajaxResponse();

	$date_curr_position_start = trim($date_curr_position_start);
	$curr_position_length = "";
	if($date_curr_position_start != '') {
		$d = new ParseBGDate();
		if($d->Parse($date_curr_position_start)) {
			$date_curr_position_start = $d->day.'.'.$d->month.'.'.$d->year;
			$curr_position_length = calcTimespan($d->day, $d->month, $d->year);
		}
		$objResponse->assign("date_curr_position_start", "value", $date_curr_position_start);
		$objResponse->assign("curr_position_length", "value", $curr_position_length);
	}
	return $objResponse;
}
function totalServiceLength($date_career_start) {
	$objResponse = new xajaxResponse();

	$date_career_start = trim($date_career_start);
	$career_length = "";
	if($date_career_start != '') {
		$d = new ParseBGDate();
		if($d->Parse($date_career_start)) {
			$date_career_start = $d->day.'.'.$d->month.'.'.$d->year;
			$career_length = calcTimespan($d->day, $d->month, $d->year);
		}
		$objResponse->assign("date_career_start", "value", $date_career_start);
		$objResponse->assign("career_length", "value", $career_length);
	}

	return $objResponse;
}
function calcBirthDate($egn) {
	$objResponse = new xajaxResponse();
	if(preg_match('/^[0-9]{10}$/',$egn)) {
		$y = substr($egn, 0, 2);
		$y = 1900 + intval($y);
		$m = substr($egn, 2, 2);
		$d = substr($egn, 4, 2);
		$sex = substr($egn, 8, 1);
		$birth_date = (false !== $ts = strtotime($y.'-'.$m.'-'.$d)) ? sprintf("%02d.%02d.%04d", $d, $m, $y) : '';
		$objResponse->assign("birth_date", "value", $birth_date);
		$objResponse->assign("sex", "value", (($sex%2) ? 'Ж' : 'М'));
	}
	return $objResponse;
}
function calcTimespan($d, $m, $y) {
	$t = new timespan(time(), mktime(0, 0, 0, $m, $d, $y));
	return $t->years.' г. и '.$t->months.' м.';
}
function formatDateRetired($date_retired) {
	$objResponse = new xajaxResponse();

	$date_retired = trim($date_retired);
	if($date_retired != '') {
		$d = new ParseBGDate();
		if($d->Parse($date_retired))
		$objResponse->assign("date_retired", "value", $d->day.'.'.$d->month.'.'.$d->year);
		else
		$objResponse->assign("date_retired", "value", "");
	}

	return $objResponse;
}
function populateAbove($map_id) {
	$objResponse = new xajaxResponse();

	if($map_id) {
		global $dbInst;
		$row = $dbInst->getMapRow($map_id);
		$objResponse->assign("subdivision_name","value",$row['subdivision_name']);
		$objResponse->assign("wplace_name","value",$row['wplace_name']);
	}
	else {
		$objResponse->assign("subdivision_name","value","");
		$objResponse->assign("wplace_name","value","");
	}

	return $objResponse;
}
function removeProRoute($route_id, $worker_id) {
	$objResponse = new xajaxResponse();

	if($_SESSION['sess_user_level'] == 1) { /* admin rights only */
		global $dbInst;
		$count = $dbInst->removeProRoute($route_id, $worker_id);
		$objResponse->assign("panel","innerHTML",echoProRoute($worker_id));
		$objResponse->assign('lastModified','innerHTML',$dbInst->getModifiedBy('workers', 'worker_id', $worker_id));
	}
	return $objResponse;
}
function loadPulldown($doctor_id=0) {
	$objResponse = new xajaxResponse();
	$html  = '<select id="doctor_id" name="doctor_id" style="width:59%;">';
	$html .= '<option value="0"> &nbsp;&nbsp;</option>';
	global $dbInst;
	$rows = $dbInst->fnSelectRows("SELECT * FROM doctors ORDER BY doctor_name");
	foreach ($rows as $row) {
		$html .= '<option value="'.$row['doctor_id'].'"'.(($doctor_id==$row['doctor_id'])?' selected="selected"':'').'>'.HTMLFormat($row['doctor_name']).'</option>';
	}
	$html .= '</select>';
	$objResponse->assign("pulldownWrapper","innerHTML",$html);
	return $objResponse;
}
$xajax = new xajax();
$xajax->registerFunction("processWorker");
$xajax->registerFunction("processProRoute");
$xajax->registerFunction("currentServiceLength");
$xajax->registerFunction("totalServiceLength");
$xajax->registerFunction("calcBirthDate");
$xajax->registerFunction("swapFirm");
$xajax->registerFunction("formatDateRetired");
$xajax->registerFunction("guessLocation");
$xajax->registerFunction("processDoctor");
$xajax->registerFunction("populateAbove");
$xajax->registerFunction("removeProRoute");
$xajax->registerFunction("loadPulldown");
//$xajax->setFlag("debug",true);
$echoJS = $xajax->getJavascript('xajax/');
$xajax->processRequest();
// Xajax end

function echoProRoute($worker_id) {
	global $dbInst;
	$f = $dbInst->getWorkerInfo($worker_id);
	$rows = $dbInst->getProRoute($worker_id);
	ob_start();
	?>
          <table border="0" cellpadding="0" cellspacing="0" class="xlstable" width="770">
            <tr>
              <th>Предприятие</th>
              <th>Длъжност/професия</th>
              <th>Продължителност <br />
                на стажа</th>
              <?php if($_SESSION['sess_user_level'] == 1) { /* admin rights only */ ?>
              <th>&nbsp;</th>
              <?php } ?>
            </tr>
            <?php
            if(isset($rows)) {
            foreach ($rows as $row) { ?>
            <tr>
              <td><input type="text" id="firm_name_<?=$row['route_id']?>" name="firm_name_<?=$row['route_id']?>" value="<?=HTMLFormat($row['firm_name'])?>" size="52" maxlength="100" /></td>
              <td><input type="text" id="position_<?=$row['route_id']?>" name="position_<?=$row['route_id']?>" value="<?=HTMLFormat($row['position'])?>" size="50" maxlength="80" /></td>
              <td align="center"><input type="text" id="exp_length_y_<?=$row['route_id']?>" name="exp_length_y_<?=$row['route_id']?>" size="2" maxlength="2" value="<?=HTMLFormat($row['exp_length_y'])?>" onkeypress="return numbersonly(this, event);" />
                г.
                <input type="text" id="exp_length_m_<?=$row['route_id']?>" name="exp_length_m_<?=$row['route_id']?>" value="<?=HTMLFormat($row['exp_length_m'])?>" size="2" maxlength="2" onkeypress="return numbersonly(this, event);" />
                м. </td>
              <?php if($_SESSION['sess_user_level'] == 1) { /* admin rights only */ ?>
              <td align="center" width="20"><a href="javascript:void(null);" onclick="var answ=confirm('Наистина ли искате да изтриете трудовия стаж?'); if(answ) { xajax_removeProRoute(<?=$row['route_id']?>, <?=$worker_id?>);} return false;" title="Изтрий трудовия стаж"><img src="img/delete.gif" width="15" height="15" border="0" alt="Изтрий" /></a></td>
              <?php } ?>
            </tr>
            <?php }} ?>
            <tr>
              <td><input type="text" id="firm_name_0" name="firm_name_0" value="" size="52" maxlength="100" class="newItem" /></td>
              <td><input type="text" id="position_0" name="position_0" value="" size="50" maxlength="80" class="newItem" /></td>
              <td align="center"><input type="text" id="exp_length_y_0" name="exp_length_y_0" size="2" maxlength="2" value="" onkeypress="return numbersonly(this, event);" class="newItem" />
                г.
                <input type="text" id="exp_length_m_0" name="exp_length_m_0" value="" size="2" maxlength="2" onkeypress="return numbersonly(this, event);" class="newItem" />
                м. </td>
              <?php if($_SESSION['sess_user_level'] == 1) { /* admin rights only */ ?>
              <td align="center">&nbsp;</td>
              <?php } ?>
            </tr>
            <tr>
              <td colspan="4"><p align="center">
                  <input type="button" id="btnSubmit" name="btnSubmit" value="Съхрани" class="nicerButtons" onclick="$('input#form_is_dirty').val(1);this.value='обработка...';this.disabled=true;xajax_processProRoute(xajax.getFormValues('frmFirm'));DisableEnableForm(true);return false;" />
                </p></td>
            </tr>
          </table>
	<?php
	$buff = ob_get_contents();
	ob_end_clean();
	return $buff;
}
function echoWorkerData($worker_id, $firmInfo) {
	global $dbInst;
	global $firm_id;
	$f = $dbInst->getWorkerInfo($worker_id);
	ob_start();
	?>
        <table cellpadding="0" cellspacing="0" class="formBg">
          <tr>
            <td colspan="4" class="leftSplit rightSplit topSplit"><strong>Име: </strong>
                <input type="text" id="fname" name="fname" value="<?=((isset($f['fname']))?HTMLFormat($f['fname']):'')?>" size="30" maxlength="50" onblur="ucfirst(this)" />
                &nbsp;Презиме: <input type="text" id="sname" name="sname" value="<?=((isset($f['sname']))?HTMLFormat($f['sname']):'')?>" size="30" maxlength="50" onblur="ucfirst(this)" />
                &nbsp;<strong>Фамилия: </strong>
                <input type="text" id="lname" name="lname" value="<?=((isset($f['lname']))?HTMLFormat($f['lname']):'')?>" size="30" maxlength="50" onblur="ucfirst(this)" />
              </td>
          </tr>
          <tr>
            <td class="leftSplit"><strong>ЕГН:</strong></td>
            <td>
                <input type="text" id="egn" name="egn" value="<?=((isset($f['egn']))?HTMLFormat($f['egn']):'')?>" size="15" maxlength="15" onKeyPress="return numbersonly(this, event);" onchange="xajax_calcBirthDate(this.value);" />
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Пол:
                <select id="sex" name="sex">
                  <option value="М"<?=((isset($f['sex'])&&$f['sex']=='М')?' selected="selected"':'')?>>Мъж &nbsp;&nbsp;&nbsp;</option>
                  <option value="Ж"<?=((isset($f['sex'])&&$f['sex']=='Ж')?' selected="selected"':'')?>>Жена &nbsp;&nbsp;&nbsp;</option>
                </select>
              </td>
            <td nowrap="nowrap">Дата на раждане:</td>
            <td class="rightSplit">
                <input type="text" id="birth_date" name="birth_date" value="<?=((isset($f['birth_date']))?$f['birth_date2']:'')?>" size="38" maxlength="10" />
                г. </td>
          </tr>
          <tr>
            <td nowrap="nowrap" class="leftSplit">Населено място: </td>
            <td>
            	<input type="text" id="location_name" name="location_name" value="<?=((isset($f['l.location_name']))?$f['l.location_name']:'')?>" size="40" maxlength="50" onchange="xajax_guessLocation(this.value);return false;" />
                <input type="hidden" id="location_id" name="location_id" value="<?=((isset($f['location_id']))?HTMLFormat($f['location_id']):'0')?>" />
              </td>
            <td>Адрес: </td>
            <td class="rightSplit">
                <input type="text" id="address" name="address" value="<?=((isset($f['address']))?HTMLFormat($f['address']):'')?>" size="40" maxlength="50" />
              </td>
          </tr>
          <tr>
            <td class="leftSplit">Тел. 1: </td>
            <td>
                <input type="text" id="phone1" name="phone1" value="<?=((isset($f['phone1']))?HTMLFormat($f['phone1']):'')?>" size="40" maxlength="50" />
              </td>
            <td>Тел. 2: </td>
            <td class="rightSplit">
                <input type="text" id="phone2" name="phone2" value="<?=((isset($f['phone2']))?HTMLFormat($f['phone2']):'')?>" size="40" maxlength="50" />
              </td>
          </tr>
          <tr>
            <td colspan="4" class="leftSplit rightSplit"><span class="labeltext">Личен лекар:</span>
              <span id="pulldownWrapper">зареждане...<script type="text/javascript">xajax_loadPulldown(<?=((isset($f['doctor_id']))?$f['doctor_id']:'0')?>);</script></span>&nbsp;&nbsp;<a href="form_doctor.php?doctor_id=0&amp;<?=SESS_NAME.'='.session_id()?>&amp;height=160&amp;width=472&amp;modal=true" title="Добави нов фамилен лекар" class="thickbox"><img src="img/newitem.gif" alt="" width="16" height="16" border="0" /> Нов фамилен лекар</a>
                </td>
          </tr>
          
          
          <?php
          $subdivision_id = (isset($f['s.subdivision_id'])) ? $f['s.subdivision_id'] : 0;
          $wplace_id = (isset($f['p.wplace_id'])) ? $f['p.wplace_id'] : 0;
          $position_id = (isset($f['i.position_id'])) ? $f['i.position_id'] : 0;
          ?>
          <tr>
            <td colspan="4" class="leftSplit rightSplit"><span class="labeltext">Подразделение: </span>
              <select id="subdivision_id" name="subdivision_id" onchange="loadWorkPositions($('#subdivision_id'), $('#wplace_id'), $('#position_id'), 'work_places');">
                <option value="0">-- изберете -- </option>
                <?php
                $flds = $dbInst->query("SELECT * FROM `subdivisions` WHERE `firm_id` = $firm_id ORDER BY `subdivision_name`, `subdivision_id`");
                if(!empty($flds)) {
                	foreach ($flds as $fld) {
                		echo '<option value="'.$fld['subdivision_id'].'"'.((!strcmp($subdivision_id, $fld['subdivision_id'])) ? ' selected="selected"' : '').'>'.HTMLFormat($fld['subdivision_name']).' </option>';
                	}
                }
                ?></select></td>
          </tr>
          <tr>
            <td colspan="4" class="leftSplit rightSplit"><span class="labeltext">Работно място: </span>
              <select id="wplace_id" name="wplace_id" onchange="loadWorkPositions($('#subdivision_id'), $('#wplace_id'), $('#position_id'), 'firm_positions');">
                <option value="0">-- изберете -- </option>
                <?php
                $sql = "SELECT w.wplace_id AS wplace_id , w.wplace_name AS wplace_name
						FROM firm_struct_map m
						LEFT JOIN work_places w ON ( w.wplace_id = m.wplace_id )
						WHERE m.firm_id = $firm_id
						AND m.subdivision_id = $subdivision_id
						ORDER BY w.wplace_name, w.wplace_id";
                $flds = $dbInst->query($sql);
                if(!empty($flds)) {
                	foreach ($flds as $fld) {
                		echo '<option value="'.$fld['wplace_id'].'"'.((!strcmp($wplace_id, $fld['wplace_id'])) ? ' selected="selected"' : '').'>'.HTMLFormat($fld['wplace_name']).' </option>';
                	}
                }
                ?></select></td>
          </tr>
          <tr>
            <td colspan="4" class="leftSplit rightSplit"><span class="labeltext">Длъжност: </span>
              <select id="position_id" name="position_id">
                <option value="0">-- изберете -- </option>
                <?php
                $sql = "SELECT p.position_id AS position_id , p.position_name AS position_name
						FROM firm_struct_map m
						LEFT JOIN firm_positions p ON ( p.position_id = m.position_id )
						WHERE m.firm_id = $firm_id
						AND m.wplace_id = $wplace_id
						ORDER BY p.position_name, p.position_id";
                $flds = $dbInst->query($sql);
                if(!empty($flds)) {
                	foreach ($flds as $fld) {
                		echo '<option value="'.$fld['position_id'].'"'.((!strcmp($position_id, $fld['position_id'])) ? ' selected="selected"' : '').'>'.HTMLFormat($fld['position_name']).' </option>';
                	}
                }
                ?></select></td>
          </tr>
          
          
          <tr>
            <td colspan="4" class="leftSplit rightSplit"><span class="labeltext">Подразделение: </span>
            	<input type="text" id="subdivision_name" name="subdivision_name" value="<?=((isset($f['s.subdivision_name']))?HTMLFormat($f['s.subdivision_name']):'')?>" size="40" maxlength="60" style="width:59%;" readonly="readonly" />
                <div class="br"></div>
            	<span class="labeltext">Работно място: </span>
            	<input type="text" id="wplace_name" name="wplace_name" value="<?=((isset($f['p.wplace_name']))?HTMLFormat($f['p.wplace_name']):'')?>" size="40" maxlength="60" style="width:59%;" readonly="readonly" />
                <div class="br"></div>
                <span class="labeltext">Длъжност: </span>
                <select id="map_id" name="map_id" style="width:59%;font-size:100%;" onchange="xajax_populateAbove(this.value);return false;">
                  <option value="0">&nbsp;</option>
                  <optgroup label="<?=HTMLFormat($firmInfo['name'].' - '.$firmInfo['location_name'])?>"></optgroup>
                  <?php
                  $rows = $dbInst->getMap($firm_id);
                  if($rows) {
                  	$subdivision_id = -1;
                  	$wplace_id = -1;
                  	$position_id = -1;
                  	$out = '';
                  	foreach ($rows as $row) {
                  		// subdivisions
                  		if($row['subdivision_id'] != $subdivision_id && $row['subdivision_id']) {
                  			$out .= '<optgroup style="background-color:#999999;" label="&nbsp;&nbsp;&nbsp;'.HTMLFormat($row['subdivision_name']).'"></optgroup>';
                  			$subdivision_id = $row['subdivision_id'];
                  			$wplace_id = -1;
                  		}
                  		// work places
                  		if($row['wplace_id'] != $wplace_id && $row['wplace_id']) {
                  			$out .= '<optgroup label="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.HTMLFormat($row['wplace_name']).'"></optgroup>';
                  			$wplace_id = $row['wplace_id'];
                  		}
                  		// firm positions
                  		//if($row['position_id'] != $position_id && $row['position_id']) {
                  		//$out .= '<option value="'.$row['map_id'].'"'.((isset($f['map_id'])&&$f['map_id']==$row['map_id'])?' selected="selected"':'').' style="padding-left:30px;background-color:#FFFFFF;">&nbsp;&nbsp;&nbsp;&nbsp;'.HTMLFormat($row['position_name']).'</option>';
                  		$out .= '<option value="'.$row['map_id'].'"'.((isset($f['map_id'])&&$f['map_id']==$row['map_id'])?' selected="selected"':'').' style="background-color:#FFFFFF;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.HTMLFormat($row['position_name']).'</option>';
                  		//$position_id = $row['position_id'];
                  		//}
                  	}
                  }
                  echo $out;
                  ?>
                </select>
              </td>
          </tr>
          <tr>
            <td colspan="4" class="leftSplit rightSplit"><span class="labeltextL">Тр. стаж по настоящата длъжност от:</span>
                <input type="text" id="date_curr_position_start" name="date_curr_position_start" value="<?=((isset($f['date_curr_position_start']))?$f['date_curr_position_start2']:'')?>" onchange="xajax_currentServiceLength(this.value);return false" size="20" maxlength="10" /> г.
                &nbsp;&nbsp;&nbsp;<img src="img/caret-r.gif" alt="" width="11" height="7" border="0" />&nbsp;&nbsp;
                <?php
                $curr_position_length = '';
                if(isset($f['date_curr_position_start']) && $f['date_curr_position_start'] != '') {
                	$date = substr($f['date_curr_position_start'], 0, 10);
                	list($y, $m, $d) = explode('-',$date);
                	$curr_position_length = calcTimespan($d, $m, $y);
                }
                ?>
                <input type="text" id="curr_position_length" name="curr_position_length" value="<?=$curr_position_length?>" size="20" maxlength="30" readonly="readonly" />
                <div class="br"></div>
                <span class="labeltextL">Общ трудов стаж от:</span>
                <input type="text" id="date_career_start" name="date_career_start" value="<?=((isset($f['date_career_start']))?$f['date_career_start2']:'')?>" onchange="xajax_totalServiceLength(this.value);return false" size="20" maxlength="10" /> г.
                общо
                <?php
                $career_length = '';
                if(isset($f['date_career_start']) && $f['date_career_start'] != '') {
                	$date = substr($f['date_career_start'], 0, 10);
                	list($y, $m, $d) = explode('-',$date);
                	$career_length = calcTimespan($d, $m, $y);
                }
                ?>
                <input type="text" id="career_length" name="career_length" value="<?=$career_length?>" size="20" maxlength="30" readonly="readonly" />
                <div class="br"></div>
                <span class="continue labeltextL"><strong>Напуснал на:</strong></span>
                <input type="text" id="date_retired" name="date_retired" value="<?=((isset($f['date_retired']))?$f['date_retired2']:'')?>" size="20" maxlength="10" onchange="xajax_formatDateRetired(this.value);return false;" />
                г. </td>
          </tr>
          <tr>
            <th colspan="4" class="leftSplit rightSplit"><p align="center">
                <input type="button" id="btnSubmit" name="btnSubmit" value="Съхрани" class="nicerButtons" onclick="postData();" />
              </p></th>
          </tr>
        </table>
	<?php
	$buff = ob_get_contents();
	ob_end_clean();
	return $buff;
}

$tab = (isset($_GET['tab'])) ? $_GET['tab'] : 'worker_data';

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?=SITE_NAME?></title>
<link href="styles.css" rel="stylesheet" type="text/css" media="screen" />
<?=$echoJS?>
<script type="text/javascript" src="js/RegExpValidate.js"></script>
<!-- http://jquery.com/demo/thickbox/ -->
<script type="text/javascript" src="js/jquery-latest.pack.js"></script>
<script type="text/javascript" src="js/thickbox/thickbox.js"></script>
<link rel="stylesheet" href="js/thickbox/thickbox.css" type="text/css" media="screen" />
<script type="text/javascript" charset="utf-8">
//<![CDATA[
$(document).ready(function() {
	stripTable('listtable');
	var parent = (window.opener) ? window.opener : self.parent;
	if(parent.document.getElementById('TB_ajaxWindowTitle')) {
		parent.document.getElementById('TB_ajaxWindowTitle').style.fontWeight = 'bold';
		// Reload the parent window when the close button of Thickbox popup is clicked!
		parent.document.getElementById('TB_closeWindowButton').onclick = function() {
			if($('input#form_is_dirty').val() != 0) {
				parent.location.reload();
			}
		};
	}
});
function stripTable(tableid) {
	// Strip table
	$("#"+tableid+" tr:even").addClass("alternate");
	// Hightlight table rows
	$("#"+tableid+" tr").hover(function() {
		$(this).addClass("over");
	},function() {
		$(this).removeClass("over");
	});
}
function switchTab(tab) {
	window.location = '<?=basename($_SERVER['PHP_SELF'])?>?firm_id=<?=$firm_id?>&worker_id='+$("input[name='worker_id']").val()+'&tab='+tab+'&<?=SESS_NAME.'='.session_id()?>';
}
function postData() {
	var fname = $.trim($('#fname').val());
	var lname = $.trim($('#lname').val());
	if(fname == '' && lname == '') {
		alert('Моля, въведете име или фамилия на работещия.');
		$('#fname').focus();
		return false;
	}
	var egn = $.trim($('#egn').val());
	if(egn == '') {
		alert('Моля, въведете ЕГН на работещия.');
		$('#egn').focus();
		return false;
	}
	if(egn.length != 10) {
		if(!confirm('Сигурни ли сте, че въведеният ЕГН е правилен?')) {
			return false;
		}
	}
	$('#form_is_dirty').val(1);
	xajax_processWorker(xajax.getFormValues('frmFirm'));
	DisableEnableForm(true);
	return false;
}
//subdivisions, work_places, firm_positions (work_places|firm_positions)
function loadWorkPositions(el1, el2, el3, target) {
	// ajax_load_work_positions: work_places / id: 101
	// ajax_load_work_positions: firm_positions / id: 367
	var id = ( target == 'work_places' ) ? el1.val() : el2.val();
	$.ajax({
		type: "post",
		url: "<?=basename($_SERVER['PHP_SELF']).((isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) ? '?'.$_SERVER['QUERY_STRING'] : '')?>",
		//dataType: "json",
		dataType: "text",
		cache: false,
		data: {
			"ajax_load_work_positions" : target ,
			"id" : id
		}, // End data
		success: function(data) {
			data = JSON.parse(data);
			el3.empty().append("<option value='0'>-- изберете -- <\/option>");
			if( target == 'work_places' ) el2.empty().append("<option value='0'>-- изберете -- <\/option>");
			$.each(data, function(i, resp) {
				if( target == 'work_places' ) el2.append("<option value='" + resp.key + "'>" + resp.value + "<\/option>");
				if( target == 'firm_positions' ) el3.append("<option value='" + resp.key + "'>" + resp.value + "<\/option>");
			});
		}, // End success
		error: function(jqXHR, textStatus, errorThrown) {
			alert("Error... " + textStatus + " / " + errorThrown);
		} // End error
	}); // End ajax method
}
//]]>
</script>
<!-- Auto-completer includes begin -->
<!-- http://dev.jquery.com/view/trunk/plugins/autocomplete/ -->
<!-- <script type="text/javascript" src="js/autocompleter/jquery.js"></script> -->
<script type='text/javascript' src='js/autocompleter/jquery.bgiframe.min.js'></script>
<!--<script type="text/javascript" src="http://dev.jquery.com/view/trunk/plugins/autocomplete/lib/jquery.bgiframe.min.js"></script>-->
<script type='text/javascript' src='js/autocompleter/jquery.dimensions.js'></script>
<script type='text/javascript' src='js/autocompleter/jquery.ajaxQueue.js'></script>
<script type='text/javascript' src='js/autocompleter/jquery.autocomplete.js'></script>
<script type='text/javascript' src='js/autocompleter/localdata.js'></script>
<!-- <link rel="stylesheet" type="text/css" href="js/autocompleter/main.css" /> -->
<link rel="stylesheet" type="text/css" href="js/autocompleter/jquery.autocomplete.css" />
<!-- Auto-completer includes end -->
<script type="text/javascript">
//<![CDATA[
// Auto-completer begin
$(document).ready(function() {

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

	$("#location_name").autocomplete("autocompleter.php", {
		minChars: 1,
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
// Auto-completer end
function ucfirst(el) {
	if(el.value != "") {
		var str = el.value;
		el.value = str.charAt(0).toUpperCase() + str.slice(1);
	}
}
//]]>
</script>
<style type="text/css">
body,html {
	background-image:none;
}
</style>
</head>
<body style="overflow:hidden;">
<div id="contentinner" align="center">
  <form id="frmFirm" action="javascript:void(null);">
    <input type="hidden" id="form_is_dirty" name="form_is_dirty" value="0" />
    <input type="hidden" id="worker_id" name="worker_id" value="<?=$worker_id?>" />
    <input type="hidden" id="firm_id" name="firm_id" value="<?=$firm_id?>" />
    <div align="center" style="width:790px;">
      <div id="lastModified" class="lastModified"><?php if($worker_id) { echo $dbInst->getModifiedBy('workers', 'worker_id', $worker_id); } else { echo '<br />'; } ?></div>
      <div id="tabs"> <a href="#" onclick="switchTab('worker_data');" class="tab<?=(($tab=='worker_data')?' active':'')?>">Данни за работещия </a> <a href="#" onclick="switchTab('pro_route');" class="tab<?=(($tab=='pro_route')?' active':'')?>">Професионален маршрут</a></div>
      <script type="text/javascript">if ( (jQuery.browser.msie && jQuery.browser.version < 7)) { document.write('<br clear="all" \/>'); }</script>
      <div id="panel" class="panel" style="display:block;<?=(('worker_data'==$tab)?'overflow:hidden;':'')?>">

      <?php
      switch ($tab) {
      	case 'pro_route':
      		echo echoProRoute($worker_id);
      		break;
      	case 'worker_data':
      	default:
      		echo echoWorkerData($worker_id, $firmInfo);
      		break;
      }
      ?>

      </div>
    </div>
  </form>
</div>
</body>
</html>
