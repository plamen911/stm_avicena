<?php
require('includes.php');

$echoJS = '';
$perPage = (isset($_GET['perPage'])) ? abs(intval($_GET['perPage'])) : 30;
$_SESSION[basename($_SERVER['PHP_SELF'])] = (isset($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : '';

// PAGER BEGIN
require_once 'Pager/Pager_Wrapper.php';
$pagerOptions = array(
'mode'    => 'Jumping',			// Sliding
'delta'   => 100,				// 2
'perPage' => $perPage,
//'separator'=>'|',
'spacesBeforeSeparator'=>0,	// number of spaces before the separator
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

$query = "  SELECT f.*, l.location_name, c.community_name, p.province_name
            FROM firms f
            LEFT JOIN locations l ON (l.location_id = f.location_id)
            LEFT JOIN communities c ON (c.community_id = f.community_id)
            LEFT JOIN provinces p ON (p.province_id = f.province_id)";

$txtCondition = " WHERE f.is_active='1'";

if(isset($_GET['btnFind'])) {	// Filter properties
	if(isset($_GET['name']) && !empty($_GET['name'])) {
		$txtCondition .= " AND f.name LIKE '%".$dbInst->checkStr(trim($_GET['name']))."%'";
	}
	if(isset($_GET['address']) && !empty($_GET['address'])) {
		$txtCondition .= " AND f.address LIKE '%".$dbInst->checkStr(trim($_GET['address']))."%'";
	}
	if(isset($_GET['invoice_num']) && !empty($_GET['invoice_num'])) {
		$IDs = array();
		$q = "SELECT DISTINCT `firm_id` FROM `acc_contracts` WHERE `invoice_num` LIKE '%".$dbInst->checkStr(trim($_GET['invoice_num']))."%'";
		$lines = $dbInst->query($q);
		if(!empty($lines)) {
			foreach ($lines as $line) {
				$IDs[] = $line['firm_id'];
			}
			$txtCondition .= " AND f.firm_id IN (".implode(', ', $IDs).")";
		} else {
			$txtCondition .= " AND f.firm_id = 0";
		}
	}
	$d = new ParseBGDate();
	if(isset($_GET['contract_date']) && !empty($_GET['contract_date']) && $d->Parse($_GET['contract_date'])) {
		$contract_date = $d->getYear().'-'.$d->getMonth().'-'.$d->getDay().' 00:00:00';
		if(!empty($_GET['contract_date2']) && !$d->Parse($_GET['contract_date2'])) {
			$contract_date2 = $d->getYear().'-'.$d->getMonth().'-'.$d->getDay().' 00:00:00';
		} else {
			$contract_date2 = date('Y-m-d', time()) . ' 00:00:00';
		}
		$IDs = array();
		$q = "SELECT DISTINCT `firm_id` FROM `acc_contracts` WHERE ";
		switch ($_GET['date_range']) {
			case 'exactly':
				$q .= "`contract_date` = '$contract_date'";
				break;
			case 'before':
				$q .= "`contract_date` < '$contract_date'";
				break;
			case 'after':
				$q .= "`contract_date` > '$contract_date'";
				break;
			case 'between':
				$q .= "`contract_date` >= '$contract_date' AND `contract_date` <= '$contract_date2'";
				break;
		}
		$lines = $dbInst->query($q);
		if(!empty($lines)) {
			foreach ($lines as $line) {
				$IDs[] = $line['firm_id'];
			}
			$txtCondition .= " AND f.firm_id IN (".implode(', ', $IDs).")";
		} else {
			$txtCondition .= " AND f.firm_id = 0";
		}
	}

}	// Search end
$sortArr = array('name','location_name','address','num_workers');
if (isset($_GET["sort_by"]) && in_array($_GET["sort_by"],$sortArr)) {
	$order = (isset($_GET['order']) && $_GET['order']=='ASC') ? 'ASC' : 'DESC';
	$txtCondition .= " ORDER BY `$_GET[sort_by]` $order, LOWER(name), l.location_name, c.community_name, p.province_name, f.firm_id";
}
else $txtCondition .= " ORDER BY LOWER(f.`name`), l.location_name, c.community_name, p.province_name, f.firm_id";

$query .= $txtCondition;
//die($query);
$db = $dbInst->getDBHandle();
$paged_data = Pager_Wrapper_PDO($db, $query, $pagerOptions);
$firms	 = $paged_data['data'];  //paged data
$links = $paged_data['links']; //xhtml links for page navigation
$current = (isset($paged_data['page_numbers']['current'])) ? $paged_data['page_numbers']['current'] : 0;
$totalItems = $paged_data['totalItems'];
$from = ($current) ? $paged_data['from'] : 0;
$to = $paged_data['to'];
// PAGER END

$echoJS .= <<<EOT
<!-- http://jonathanleighton.com/projects/date-input -->
<script type="text/javascript" src="js/date_input/jquery.date_input.js"></script>
<link rel="stylesheet" href="js/date_input/date_input.css" type="text/css"/>
<script type="text/javascript">
//<![CDATA[
jQuery.extend(DateInput.DEFAULT_OPTS, {
  month_names: ["Януари","Февруари","Март","Април","Май","Юни","Юли","Август","Септември","Октомври","Ноември","Декември"],
  short_month_names: ["Яну","Фев","Мар","Апр","Май","Юни","Юли","Авг","Сеп","Окт","Ное","Дек"],
  short_day_names: ["Нд", "Пн", "Вт", "Ср", "Чт", "Пт", "Сб"]
});
$($.date_input.initialize);
$(document).ready(function() {
	// Strip table
	//$("table.carlist tr:odd").addClass("selected");
	// Hightlight table rows
	$("table.highlight tr").not(".notover").hover(function() {
		$(this).addClass("tr_highlight");
	},function() {
		$(this).removeClass("tr_highlight");
	});
	$("select#date_range").change(function(){
		if($(this).val()=="between"){
			$("span#span_contract_date2").css("display","inline");
		} else {
			$("span#span_contract_date2").hide();
		}
	});
});
//]]>
</script>
EOT;

include("acc_header.php");
?>
    <form id="frmFirm" action="<?=basename($_SERVER['PHP_SELF'])?>" method="get">
      <div class="divider1">&nbsp;</div>
      <table class="listing" cellpadding="4" cellspacing="2" width="99%">
        <tr>
          <td>Фирма
            <input type="text" id="name" name="name" value="<?=((isset($_GET['name']))?HTMLFormat($_GET['name']):'')?>" size="30" maxlength="255" />
            &nbsp;&nbsp;Адрес
            <input type="text" id="address" name="address" value="<?=((isset($_GET['address']))?HTMLFormat($_GET['address']):'')?>" size="30" maxlength="255" />
            &nbsp;&nbsp;Фактура No
            <input type="text" id="invoice_num" name="invoice_num" value="<?=((isset($_GET['invoice_num']))?HTMLFormat($_GET['invoice_num']):'')?>" size="30" maxlength="255" />
          </td>
        </tr>
        <tr>
          <td>Договор, сключен &nbsp;
            <select id="date_range" name="date_range">
              <option value="exactly"<?=((isset($_GET['date_range'])&&'exactly'==$_GET['date_range'])?' selected="selected"':'')?>>на </option>
              <option value="before"<?=((isset($_GET['date_range'])&&'before'==$_GET['date_range'])?' selected="selected"':'')?>>преди </option>
              <option value="after"<?=((isset($_GET['date_range'])&&'after'==$_GET['date_range'])?' selected="selected"':'')?>>след </option>
              <option value="between"<?=((isset($_GET['date_range'])&&'between'==$_GET['date_range'])?' selected="selected"':'')?>>между </option>
            </select>
            &nbsp;
            <input type="text" id="contract_date" name="contract_date" value="<?=((isset($_GET['contract_date']))?HTMLFormat($_GET['contract_date']):'')?>" size="15" class="date_input" />
            г. <span id="span_contract_date2"<?=((isset($_GET['date_range'])&&'between'==$_GET['date_range'])?'':' style="display:none"')?>>и
            <input type="text" id="contract_date2" name="contract_date2" value="<?=((isset($_GET['contract_date2']))?HTMLFormat($_GET['contract_date2']):'')?>" size="15" class="date_input" />
            г.</span></td>
        </tr>
        <tr>
          <td><input type="submit" id="btnFind" name="btnFind" value="Търси" class="nicerButton" /></td>
        </tr>
      </table>
      <div class="divider1">&nbsp;</div>
      <div class="breadcrumbs pageline" align="right">Резултати <?=$from?> - <?=$to?> от <?=$totalItems?><?php if($paged_data['links']) { ?> / Иди на страница <?='<br />'.$paged_data['links']?><?php } ?></div>
      <table class="listing highlight" cellpadding="4" cellspacing="0" width="99%">
        <tr class="notover">
          <th><?php if (isset($_GET["sort_by"])&&$_GET["sort_by"]=="name"){?><img src="img/<?php if (isset($_GET["order"])&&$_GET["order"]=="DESC"){ ?>s_desc.png<?php } else { ?>s_asc.png<?php } ?>" alt="Sort" width="11" height="9" border="0" /><?php } ?>
              <a href="<?=basename($_SERVER['PHP_SELF']).cleanQueryString('sort_by=name&order='.((isset($_GET["sort_by"])&&$_GET["sort_by"]=="name")?(($_GET["order"]=="DESC")?"ASC":"DESC"):"ASC"))?>" title="Сортиране по наименование">Наименование</a></th>
          <th><?php if (isset($_GET["sort_by"])&&$_GET["sort_by"]=="location_name"){?><img src="img/<?php if (isset($_GET["order"])&&$_GET["order"]=="DESC"){ ?>s_desc.png<?php } else { ?>s_asc.png<?php } ?>" alt="Sort" width="11" height="9" border="0" /><?php } ?>
              <a href="<?=basename($_SERVER['PHP_SELF']).cleanQueryString('sort_by=location_name&order='.((isset($_GET["sort_by"])&&$_GET["sort_by"]=="location_name")?(($_GET["order"]=="DESC")?"ASC":"DESC"):"ASC"))?>" title="Сортиране по населено място">Населено място</a></th>
          <th><?php if (isset($_GET["sort_by"])&&$_GET["sort_by"]=="address"){?><img src="img/<?php if (isset($_GET["order"])&&$_GET["order"]=="DESC"){ ?>s_desc.png<?php } else { ?>s_asc.png<?php } ?>" alt="Sort" width="11" height="9" border="0" /><?php } ?>
              <a href="<?=basename($_SERVER['PHP_SELF']).cleanQueryString('sort_by=address&order='.((isset($_GET["sort_by"])&&$_GET["sort_by"]=="address")?(($_GET["order"]=="DESC")?"ASC":"DESC"):"ASC"))?>" title="Сортиране по адрес">Адрес</a></th>
          <th>Бр. работещи</th>
        </tr>
        <?php
        if(!empty($firms) && count($firms)) {
        	foreach ($firms as $row) {
        		$field =$dbInst->fnSelectSingleRow("SELECT COUNT(*) AS cnt FROM workers w WHERE w.firm_id=$row[firm_id] AND w.date_retired='' AND w.is_active='1'");
        		$num_workers = $field['cnt'];
        ?>
        <tr>
          <td><a href="acc_firm_info.php?firm_id=<?=$row['firm_id']?>" title="Отвори/Редактирай <?=HTMLFormat($row['name'])?>"><?=HTMLFormat($row['name'])?></a></td>
          <td><?=HTMLFormat($row['location_name'])?>&nbsp;</td>
          <td><?=HTMLFormat($row['address'])?>&nbsp;</td>
          <td align="center"><?=$num_workers?></td>
        </tr>
        <?php
        	}
        } else {
        ?>
        <tr>
          <td align="left" colspan="9">Няма намерени резултати.</td>
        </tr>
        <?php } ?>
        <tr class="notover">
          <td colspan="4" align="right"><div class="divider1"></div><a href="acc_firm_info.php">Нова фирма</a>&nbsp;</td>
        </tr>
      </table>
      <div class="breadcrumbs pageline" align="right">Резултати <?=$from?> - <?=$to?> от <?=$totalItems?><?php if($paged_data['links']) { ?> / Иди на страница <?='<br />'.$paged_data['links']?><?php } ?></div>
      <div class="divider1">&nbsp;</div>
    </form>

<?php include("acc_footer.php"); ?>