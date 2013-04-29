<?php
// http://localhost/stm2008/hipokrat/acc_w_expired_contracts.php?date_from=01.01.2007&date_to=10.01.2007&offline=1
require('includes.php');

$offline = (isset($_GET['offline']) && $_GET['offline'] == '1') ? 1 : 0;

$s = $dbInst->getStmInfo();

$stm_name = preg_replace('/\<br\s*\/?\>/', '', $s['stm_name']);

if(!isset($_GET['date_from']) || trim($_GET['date_from']) == '') {
	$y = date('Y') - 1;
	$date_from = date('Y-m-d', mktime(0,0,0,1,1,$y));
	$date_to = date('Y-m-d', mktime(23,59,59,12,31,$y));
} else {
	$d = new ParseBGDate();
	if($d->Parse($_GET['date_from']))
	$date_from = $d->year.'-'.$d->month.'-'.$d->day;
	else
	$date_from = '';
	if($d->Parse($_GET['date_to']))
	$date_to = $d->year.'-'.$d->month.'-'.$d->day;
	else
	$date_to = '';
	if($date_from == '' || $date_to == '') {
		$y = date('Y') - 1;
		$date_from = date('Y-m-d', mktime(0,0,0,1,1,$y));
		$date_to = date('Y-m-d', mktime(23,59,59,12,31,$y));
	}
}

if(!$offline) {
	$period = str_replace(', ', '_', $dbInst->extractYear($date_from, $date_to));
	$period = str_replace(' и ', '_', $period);

	require_once("cyrlat.class.php");
	$cyrlat = new CyrLat;
	$filename = 'Expired_Contracts_'.$cyrlat->cyr2lat($period).'.doc';

	header("Pragma: public");
	header("Content-Disposition: attachment; filename=\"$filename\";");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	//header("Cache-Control: private", false);
	header("Content-Type: application/octet-stream");
	//header("Content-type: application/msword;");
	//$imgpath = str_replace('/','\\',str_replace(basename($_SERVER['PHP_SELF']),'',$_SERVER["SCRIPT_FILENAME"])).'img\\';
}

$sql = "SELECT c.*, f.`name` AS `firm_name`, f.`address`, L.`location_name`
		FROM `acc_contracts` c
		LEFT JOIN `firms` f ON (f.`firm_id` = c.`firm_id`)
		LEFT JOIN `locations` L ON (L.`location_id` = f.`location_id`)
		WHERE c.`contract_halt` = '0' 
		AND f.`is_active` = '1'
		AND c.`contract_end_date` >= '$date_from'
		AND c.`contract_end_date` <= '$date_to'
		ORDER BY c.`contract_end_date`, c.`contract_start_date`, `firm_name`";
$rows = $dbInst->query($sql);
$num_rows = (!empty($rows)) ? count($rows) : 0;

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
	{size:595.3pt 841.9pt;
	margin:27.0pt 28.3pt 27.0pt 45.0pt;}
div.Section1
	{page:Section1;}
-->
</style>

</head>

<body lang=BG>

<div class=Section1>

<p class=MsoNormal align=center style='text-align:center'><b><u><?=((isset($stm_name))?HTMLFormat($stm_name):'СЛУЖБА ПО ТРУДОВА МЕДИЦИНА')?></u></b></p>

<p class=MsoNormal>&nbsp;</p>

<p class=MsoNormal align=center style='text-align:center'><b><span
style='font-size:16.0pt'>С П Р А В К А</span></b></p>

<p class=MsoNormal align=center style='text-align:center'><b>&nbsp;</b></p>

<p class=MsoNormal style='margin-left:70.5pt;text-indent:-70.5pt'><b><u>ОТНОСНО:</u></b>
 Сключените договори, които изтичат през периода от <?=date('d.m.Y', strtotime($date_from))?> г. до <?=date('d.m.Y', strtotime($date_to))?> 
г.</p>

<p class=MsoNormal><b>Общ брой договори:</b> <?=$num_rows?></p>

<p class=MsoNormal>&nbsp;</p>

<?php if(!empty($num_rows)) { ?>
<table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0 width="100%"
 style='width:100.0%;border-collapse:collapse'>
 <tr>
  <td width="6%" valign=top style='width:6.08%;border:solid windowtext 1.0pt;
  padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal align=center style='text-align:center'><b>N</b></p>
  </td>
  <td width="30%" valign=top style='width:30.82%;border:solid windowtext 1.0pt;
  border-left:none;padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal align=center style='text-align:center'><b>Фирма</b></p>
  </td>
  <td width="12%" valign=top style='width:12.9%;border:solid windowtext 1.0pt;
  border-left:none;padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal align=center style='text-align:center'><b>Нас. място</b></p>
  </td>
  <td width="20%" valign=top style='width:20.48%;border:solid windowtext 1.0pt;
  border-left:none;padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal align=center style='text-align:center'><b>Адрес</b></p>
  </td>
  <td width="15%" valign=top style='width:15.2%;border:solid windowtext 1.0pt;
  border-left:none;padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal align=center style='text-align:center'><b>Дата на
  сключване </b></p>
  </td>
  <td width="14%" valign=top style='width:14.52%;border:solid windowtext 1.0pt;
  border-left:none;padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal align=center style='text-align:center'><b>Дата на изтичане
  </b></p>
  </td>
 </tr>
 <?php $i = 1; foreach ($rows as $row) { ?>
 <tr>
  <td width="6%" valign=top style='width:6.08%;border:solid windowtext 1.0pt;
  border-top:none;padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal><?=$i++?></p>
  </td>
  <td width="30%" valign=top style='width:30.82%;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal><?=HTMLFormat($row['firm_name'])?></p>
  </td>
  <td width="12%" valign=top style='width:12.9%;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal><?=HTMLFormat($row['location_name'])?></p>
  </td>
  <td width="20%" valign=top style='width:20.48%;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal><?=HTMLFormat($row['address'])?>&nbsp;</p>
  </td>
  <td width="15%" valign=top style='width:15.2%;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal><?=((!empty($row['contract_start_date']))?date('d.m.Y', strtotime($row['contract_start_date'])):'')?> г.</p>
  </td>
  <td width="14%" valign=top style='width:14.52%;border-top:none;border-left:
  none;border-bottom:solid windowtext 1.0pt;border-right:solid windowtext 1.0pt;
  padding:0cm 5.4pt 0cm 5.4pt'>
  <p class=MsoNormal><?=((!empty($row['contract_end_date']))?date('d.m.Y', strtotime($row['contract_end_date'])):'')?> г.</p>
  </td>
 </tr>
 <?php } ?>
</table>
<?php } else { ?>
<p class=MsoNormal>Няма договори, които да изтичат през периода от <?=date('d.m.Y', strtotime($date_from))?> г. до <?=date('d.m.Y', strtotime($date_to))?>
г.</p>
<?php } ?>

<p class=MsoNormal>&nbsp;</p>

<p class=MsoNormal>&nbsp;</p>

<p class=MsoNormal>&nbsp;</p>

<p class=MsoNormal><?=date('d.m.Y')?> г.</p>

<p class=MsoNormal>гр.Плевен</p>

<p class=MsoNormal>&nbsp;</p>

</div>

</body>

</html>
