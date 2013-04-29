<?php
require('includes.php');
//restrictAccessToPage();

$echoJS = '';

if($_SESSION['sess_access_level'] > 1) { 
	header('Location: acc_account.php');
	exit();
}

if(isset($_GET['del']) && !empty($_GET['del']) && is_numeric($_GET['del'])) {
	$dbInst->query(sprintf("DELETE FROM `acc_users` WHERE `id` = %d", $_GET['del']));
	
	setFlash('Акаунтът бе успешно премахнат от системата.');
	header('Location: '.basename($_SERVER['PHP_SELF']));
	exit();
}

$echoJS .= <<<EOT
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
});
//]]>
</script>
EOT;

$ptitle = SITE_NAME .' : Администрация : Списък на съществуващите акаунти';
include("acc_header.php");
?>
      <div class="divider1">&nbsp;</div>
        <?php if('' != ($msg = getFlash())) { ?><div class="err"><?=$msg?></div><?php } ?>
        <h3 class="notover">Съществуващи акаунти</h3>
        <table class="listing highlight" cellpadding="4" cellspacing="0" width="99%">
          <tr class="notover">
            <th>No</th>
            <th>Име</th>
            <th>Презиме</th>
            <th>Фамилия</th>
            <th>&nbsp;</th>
            <th>&nbsp;</th>
          </tr>
          <?php
          $rows = $dbInst->query("SELECT * FROM `acc_users` WHERE `id` != $_SESSION[sess_id] ORDER BY `fname`, `id`");
          if(is_array($rows) && count($rows)) {
          	$i = 1;
          	foreach ($rows as $row) {
          		?>
          <tr>
            <td><?=$i?>.</td>
            <td><?=HTMLFormat($row['fname'])?> &nbsp;</td>
            <td><?=HTMLFormat($row['sname'])?> &nbsp;</td>
            <td><?=HTMLFormat($row['lname'])?> &nbsp;</td>
            <td align="right"><a href="acc_account.php?id=<?=$row['id']?>" title="Редактиране на акаунта на <?=HTMLFormat(trim($row['fname'].' '.$row['lname']))?>">Редактирай</a></td>
            <td align="right"><a href="<?=basename($_SERVER['PHP_SELF'])?>?del=<?=$row['id']?>" onclick="var resp=confirm('Наистина ли искате да изтриете акаунта на <?=HTMLFormat(trim($row['fname'].' '.$row['lname']))?>?');if(!resp){return false;}">Изтрий</a></td>
          </tr>
          		<?php
          		$i++;
          	}
          } else {
          	echo '<tr>';
          	echo '<td colspan="6">Няма създадени други акаунти.</td>';
          	echo '</tr>';
          }
          ?>
          <tr class="notover">
            <td colspan="6" align="right"><div class="divider1"></div><a href="acc_account.php">Създаване на нов акаунт</a> /
        	  <a href="acc_account.php?id=<?=$_SESSION['sess_id']?>">Моят акаунт</a></td>
          </tr>
        </table>
      <div class="divider1">&nbsp;</div>

<?php include("acc_footer.php"); ?>