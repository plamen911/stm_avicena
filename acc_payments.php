<?php
require('includes.php');
//restrictAccessToPage();

if($_SESSION['sess_access_level'] > 1) {
	header('Location: acc_firms.php');
	exit();
}

$echoJS = '';

$GLOBALS['totalAmountDue'] = 0;


$echoJS = <<<EOT
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
	$("a#lnkPayments").click(function(){
		window.location='acc_w_payments.php?date_from='+$("input#date_from").val()+'&date_to='+$("input#date_to").val();
	});
	$("a#lnkExpired").click(function(){
		window.location='acc_w_expired_contracts.php?date_from='+$("input#date_from2").val()+'&date_to='+$("input#date_to2").val();
	});
});
//]]>
</script>
EOT;

function getDuePaymentsTable($due_from=0, $due_to=0, $label='') {
	$totalAmountDue = 0;

	ob_start(); // Turn output buffering on.

	$rows = getDuePayments($due_from, $due_to, &$totalAmountDue);
	
	echo '<div class="divider1">&nbsp;</div>';
	
	if(is_array($rows) && count($rows)>0) {
		if(!empty($label)) { echo '<h3>'.$label.' '.((!empty($totalAmountDue))?'(общо '.number_format($totalAmountDue, 2, '.', ' ').' лв.)':'').'</h3>'; }
	?>
      <table class="listing highlight" cellpadding="4" cellspacing="0" width="99%">
        <tr class="notover">
          <th>No на фактура</th>
          <th>Срок на договора</th>
          <th>Фирма</th>
          <th>Нас. място</th>
          <th>Платено</th>
          <th>Дължимо</th>
          <th>Обща сума</th>
          <th>Падеж</th>
          <th>&nbsp;</th>
        </tr>
        <?php foreach ($rows as $row) { ?>
        <tr>
          <td><?=HTMLFormat($row['invoice_num'])?>&nbsp;</td>
          <td align="center" nowrap="nowrap"><?=((!empty($row['contract_start_date']))?date('d.m.Y', strtotime($row['contract_start_date'])).' - '.date('d.m.Y', strtotime($row['contract_end_date'])):'&nbsp;')?></td>
          <td><?=HTMLFormat($row['firm_name'])?></td>
          <td><?=HTMLFormat($row['l.location_name'])?></td>
          <td nowrap="nowrap" align="right"><?=number_format($row['AMOUNTDUE'], 2, ',', ' ')?> лв.</td>
          <td nowrap="nowrap" align="right"><?=number_format($row['REMINDER'], 2, ',', ' ')?> лв.</td>
          <td nowrap="nowrap" align="right"><?=number_format($row['TOTAL'], 2, ',', ' ')?> лв.</td>
          <td align="center" class="<?=((time()>=$row['DUEON_TIMESTAMP'])?'today1':'forthcoming')?>"><?=$row['DUEON']?>&nbsp;</td>
          <td nowrap="nowrap" align="right"><a href="acc_contract.php?firm_id=<?=$row['firm_id']?>&amp;contract_id=<?=$row['contract_id']?>#payments" title="Плащане по фактура No <?=HTMLFormat($row['invoice_num'])?>">Плащане&gt;&gt;</a></td>
        </tr>
        <?php } ?>
      </table>
      <?php } else { ?>
      <h3>Няма плащания през периода <?=date('d.m.Y', strtotime($due_from)).' г.'?><?=(($due_to)?' - '.date('d.m.Y', strtotime($due_to)).' г.':'')?></h3>
      <?php } ?>

	<?php
	$out = ob_get_contents(); // Return the contents of the output buffer
	ob_end_clean(); // Clean (erase) the output buffer and turn off output buffering
	return $out;
}

$today = mktime(0, 0, 0, date('n'), date('j'), date('Y'));
$oneDayInSeconds = 60 * 60 * 24;

$ptitle = SITE_NAME .' : Просрочени и предстоящи плащания';
include("acc_header.php");
?>

    <div class="divider1">&nbsp;</div>
    <table class="listing" cellpadding="4" cellspacing="2" width="99%">
      <tr>
        <td><a id="lnkPayments" href="#">Справка за плащания по договори</a> за периода от
    	  <input type="text" id="date_from" name="date_from" value="" size="15" class="date_input" />
    	  г. до
    	  <input type="text" id="date_to" name="date_to" value="<?=date('d.m.Y')?>" size="15" class="date_input" />
    	  г.</td>
      </tr>
      <tr>
    	<td><a id="lnkExpired" href="#">Справка за изтичащи договори</a> през периода от
    	  <input type="text" id="date_from2" name="date_from2" value="" size="15" class="date_input" />
    	  г. до
    	  <input type="text" id="date_to2" name="date_to2" value="<?=date('d.m.Y')?>" size="15" class="date_input" />
    	  г.</td>
      </tr>
    </table>

	<?php	  
	echo getDuePaymentsTable( date('Y-m-d H:i:s', $today), 0, 'Просрочени плащания на договори' );
	echo getDuePaymentsTable( date('Y-m-d H:i:s', $today), date('Y-m-d H:i:s', ($today + $oneDayInSeconds * 7)), 'Плащания до 1 седмица' );
	echo getDuePaymentsTable( date('Y-m-d H:i:s', ($today + $oneDayInSeconds * 7)), date('Y-m-d H:i:s', ($today + $oneDayInSeconds * 14)), 'Плащания до 2 седмици' );
	echo getDuePaymentsTable( date('Y-m-d H:i:s', ($today + $oneDayInSeconds * 14)), date('Y-m-d H:i:s', ($today + $oneDayInSeconds * 21)), 'Плащания до 3 седмици' );
	?>
	<div class="divider1">&nbsp;</div>
	
<?php include("acc_footer.php"); ?>