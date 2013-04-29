<?php
// http://localhost/stm2008/hipokrat/acc_w_payments.php?date_from=01.01.2007&date_to=31.12.2008&offline=1
require('includes.php');

$offline = (isset($_GET['offline']) && $_GET['offline'] == '1') ? 1 : 0;

$s = $dbInst->getStmInfo();

$stm_name = preg_replace('/\<br\s*\/?\>/', '', $s['stm_name']);

if(!isset($_GET['date_from']) || trim($_GET['date_from']) == '') {
	$y = date('Y') - 1;
	$date_from = date('Y-m-d H:i:s', mktime(0,0,0,1,1,$y));
	$date_to = date('Y-m-d H:i:s', mktime(23,59,59,12,31,$y));
} else {
	$d = new ParseBGDate();
	if($d->Parse($_GET['date_from']))
	$date_from = $d->year.'-'.$d->month.'-'.$d->day.' 00:00:00';
	else
	$date_from = '';
	if($d->Parse($_GET['date_to']))
	$date_to = $d->year.'-'.$d->month.'-'.$d->day.' 23:59:59';
	else
	$date_to = '';
	if($date_from == '' || $date_to == '') {
		$y = date('Y') - 1;
		$date_from = date('Y-m-d H:i:s', mktime(0,0,0,1,1,$y));
		$date_to = date('Y-m-d H:i:s', mktime(23,59,59,12,31,$y));
	}
}

if(!$offline) {
	$period = str_replace(', ', '_', $dbInst->extractYear($date_from, $date_to));
	$period = str_replace(' и ', '_', $period);

	require_once("cyrlat.class.php");
	$cyrlat = new CyrLat;
	$filename = 'Payments_'.$cyrlat->cyr2lat($period).'.doc';

	header("Pragma: public");
	header("Content-Disposition: attachment; filename=\"$filename\";");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	//header("Cache-Control: private", false);
	header("Content-Type: application/octet-stream");
	//header("Content-type: application/msword;");
	//$imgpath = str_replace('/','\\',str_replace(basename($_SERVER['PHP_SELF']),'',$_SERVER["SCRIPT_FILENAME"])).'img\\';
}

$totalAmountPaid = 0;
$lines = getPaidAmounts($date_from, $date_to);

$totalAmountDue = 0;
$rows = getDuePayments($date_from, $date_to);

?><html>

<head>
<meta http-equiv=Content-Type content="text/html; charset=utf-8">
<meta name=Generator content="Microsoft Word 11 (filtered)">
<title><?=((isset($stm_name))?HTMLFormat($stm_name):'СЛУЖБА ПО ТРУДОВА МЕДИЦИНА')?></title>
<style>
<!--
 /* Style Definitions */
 p.MsoNormal, li.MsoNormal, div.MsoNormal
	{margin:0cm;
	margin-bottom:.0001pt;
	font-size:12.0pt;
	font-family:"Times New Roman";}
@page Section1
	{size:841.9pt 595.3pt;
	margin:70.9pt 70.9pt 70.9pt 70.9pt;}
div.Section1
	{page:Section1;}
-->
</style>

</head>

<body lang=BG>

<div class=Section1>

<p class=MsoNormal align=center style='text-align:center'><span
style='font-size:20.0pt'>Справка</span><span style='font-size:16.0pt'> </span></p>

<p class=MsoNormal align=center style='text-align:center'><span
style='font-size:14.0pt'>за плащания по договори за периода от <?=date('d.m.Y', strtotime($date_from))?> г. до
<?=date('d.m.Y', strtotime($date_to))?> г.</span></p>

<p class=MsoNormal>&nbsp;</p>

<p class=MsoNormal>&nbsp;</p>

<p class=MsoNormal><u>Постъпили</u> плащания: <b><?=number_format($totalAmountPaid, 2, '.', ' ')?> </b>лв.</p>

<p class=MsoNormal><u>Просрочени</u> плащания: <b><?=number_format($totalAmountDue, 2, '.', ' ')?> </b>лв.</p>

<p class=MsoNormal>&nbsp;</p>

<p class=MsoNormal>I. <u>Постъпили</u> плащания по договори през периода <?=((!empty($totalAmountPaid))?'(общо <b>'.number_format($totalAmountPaid, 2, '.', ' ').'</b> лв.)':'')?></p>

<p class=MsoNormal>&nbsp;</p>

<?php if(is_array($lines) && count($lines) > 0) { ?>
<table class=MsoTableGrid border=1 cellspacing=0 cellpadding=0 width="100%"
 style='width:100.0%;border-collapse:collapse;border:none'>
 <tr>
  <td width=43 style='width:32.4pt;border:solid windowtext 1.0pt;padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal align=center style='text-align:center'><b>N по ред</b></p>
  </td>
  <td width=84 style='width:63.0pt;border:solid windowtext 1.0pt;border-left:
  none;padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal align=center style='text-align:center'><b>No на фактура</b></p>
  </td>
  <td width=180 style='width:135.0pt;border:solid windowtext 1.0pt;border-left:
  none;padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal align=center style='text-align:center'><b>Срок на договора</b></p>
  </td>
  <td width=234 style='width:175.85pt;border:solid windowtext 1.0pt;border-left:
  none;padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal align=center style='text-align:center'><b>Фирма</b></p>
  </td>
  <td width=198 style='width:148.15pt;border:solid windowtext 1.0pt;border-left:
  none;padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal align=center style='text-align:center'><b>Нас. място</b></p>
  </td>
  <td width=108 style='width:81.0pt;border:solid windowtext 1.0pt;border-left:
  none;padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal align=center style='text-align:center'><b>Дата на плащане</b></p>
  </td>
  <td width=101 style='width:75.5pt;border:solid windowtext 1.0pt;border-left:
  none;padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal align=center style='text-align:center'><b>Платена сума,
  лв.</b></p>
  </td>
 </tr>
 <?php $i = 1; foreach ($lines as $line) { ?>
 <tr>
  <td width=43 valign=top style='width:32.4pt;border:solid windowtext 1.0pt;
  border-top:none;padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal><?=$i++?></p>
  </td>
  <td width=84 valign=top style='width:63.0pt;border-top:none;border-left:none;
  border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal><?=HTMLFormat($line['invoice_num'])?></p>
  </td>
  <td width=180 valign=top style='width:135.0pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal><?=((!empty($line['contract_start_date']))?date('d.m.Y', strtotime($line['contract_start_date'])).' - '.date('d.m.Y', strtotime($line['contract_end_date'])):'&nbsp;')?></p>
  </td>
  <td width=234 valign=top style='width:175.85pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal><?=HTMLFormat($line['firm_name'])?></p>
  </td>
  <td width=198 valign=top style='width:148.15pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal><?=HTMLFormat($line['l.location_name'])?></p>
  </td>
  <td width=108 valign=top style='width:81.0pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal><?=$line['PAIDON']?></p>
  </td>
  <td width=101 valign=top style='width:75.5pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal align=right style='text-align:right'><?=number_format($line['AMOUNTPAID'], 2, ',', ' ')?></p>
  </td>
 </tr>
 <?php } ?>
 <tr>
  <td width=43 valign=top style='width:32.4pt;border:solid windowtext 1.0pt;
  border-top:none;padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal>&nbsp;</p>
  </td>
  <td width=84 valign=top style='width:63.0pt;border-top:none;border-left:none;
  border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal>&nbsp;</p>
  </td>
  <td width=180 valign=top style='width:135.0pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal>&nbsp;</p>
  </td>
  <td width=234 valign=top style='width:175.85pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal>&nbsp;</p>
  </td>
  <td width=198 valign=top style='width:148.15pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal>&nbsp;</p>
  </td>
  <td width=108 valign=top style='width:81.0pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal><b>ОБЩО</b></p>
  </td>
  <td width=101 valign=top style='width:75.5pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal align=right style='text-align:right'><b><?=number_format($totalAmountPaid, 2, '.', ' ')?></b></p>
  </td>
 </tr>
</table>
<?php } else { ?>
<p class=MsoNormal>Няма постъпили плащания през периода <?=date('d.m.Y', strtotime($date_from)).' г.'?><?=(($date_to)?' - '.date('d.m.Y', strtotime($date_to)).' г.':'')?></p>
<?php } ?>

<p class=MsoNormal>&nbsp;</p>

<p class=MsoNormal>II. <u>Просрочени</u> плащания по договори през периода <?=((!empty($totalAmountDue))?'(общо <b>'.number_format($totalAmountDue, 2, '.', ' ').'</b> лв.)':'')?></p>

<p class=MsoNormal>&nbsp;</p>

<?php if(is_array($rows) && count($rows) > 0) { ?>
<table class=MsoTableGrid border=1 cellspacing=0 cellpadding=0 width="100%"
 style='width:100.0%;border-collapse:collapse;border:none'>
 <tr>
  <td width=43 style='width:32.4pt;border:solid windowtext 1.0pt;padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal align=center style='text-align:center'><b>N по ред</b></p>
  </td>
  <td width=84 style='width:63.0pt;border:solid windowtext 1.0pt;border-left:
  none;padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal align=center style='text-align:center'><b>No на фактура</b></p>
  </td>
  <td width=180 style='width:135.0pt;border:solid windowtext 1.0pt;border-left:
  none;padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal align=center style='text-align:center'><b>Срок на договора</b></p>
  </td>
  <td width=234 style='width:175.85pt;border:solid windowtext 1.0pt;border-left:
  none;padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal align=center style='text-align:center'><b>Фирма</b></p>
  </td>
  <td width=198 style='width:148.15pt;border:solid windowtext 1.0pt;border-left:
  none;padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal align=center style='text-align:center'><b>Нас. място</b></p>
  </td>
  <td width=108 style='width:81.0pt;border:solid windowtext 1.0pt;border-left:
  none;padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal align=center style='text-align:center'><b>Очаквана дата на
  плащане</b></p>
  </td>
  <td width=101 style='width:75.5pt;border:solid windowtext 1.0pt;border-left:
  none;padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal align=center style='text-align:center'><b>Очаквана  сума,
  лв.</b></p>
  </td>
 </tr>
 <?php $i = 1; foreach ($rows as $row) { ?>
 <tr>
  <td width=43 valign=top style='width:32.4pt;border:solid windowtext 1.0pt;
  border-top:none;padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal><?=$i++?></p>
  </td>
  <td width=84 valign=top style='width:63.0pt;border-top:none;border-left:none;
  border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal><?=HTMLFormat($row['invoice_num'])?></p>
  </td>
  <td width=180 valign=top style='width:135.0pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal><?=((!empty($row['contract_start_date']))?date('d.m.Y', strtotime($row['contract_start_date'])).' - '.date('d.m.Y', strtotime($row['contract_end_date'])):'&nbsp;')?></p>
  </td>
  <td width=234 valign=top style='width:175.85pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal><?=HTMLFormat($row['firm_name'])?></p>
  </td>
  <td width=198 valign=top style='width:148.15pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal><?=HTMLFormat($row['l.location_name'])?></p>
  </td>
  <td width=108 valign=top style='width:81.0pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal><?=$row['DUEON']?></p>
  </td>
  <td width=101 valign=top style='width:75.5pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal align=right style='text-align:right'><?=number_format($row['AMOUNTDUE'], 2, ',', ' ')?></p>
  </td>
 </tr>
 <?php } ?>
 <tr>
  <td width=43 valign=top style='width:32.4pt;border:solid windowtext 1.0pt;
  border-top:none;padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal>&nbsp;</p>
  </td>
  <td width=84 valign=top style='width:63.0pt;border-top:none;border-left:none;
  border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal>&nbsp;</p>
  </td>
  <td width=180 valign=top style='width:135.0pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal>&nbsp;</p>
  </td>
  <td width=234 valign=top style='width:175.85pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal>&nbsp;</p>
  </td>
  <td width=198 valign=top style='width:148.15pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal>&nbsp;</p>
  </td>
  <td width=108 valign=top style='width:81.0pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal><b>ОБЩО</b></p>
  </td>
  <td width=101 valign=top style='width:75.5pt;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal align=right style='text-align:right'><b><?=number_format($totalAmountDue, 2, '.', ' ')?></b></p>
  </td>
 </tr>
</table>
<?php } else { ?>
<p class=MsoNormal>Няма просрочени плащания през периода <?=date('d.m.Y', strtotime($date_from)).' г.'?><?=(($date_to)?' - '.date('d.m.Y', strtotime($date_to)).' г.':'')?></p>
<?php } ?>

<p class=MsoNormal>&nbsp;</p>

</div>

</body>

</html>
