<?php
require('includes.php');
//restrictAccessToPage();

$echoJS = '';

$id = (isset($_REQUEST['id']) && '' != $_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
if($_SESSION['sess_access_level'] > 1) { $id = $_SESSION['sess_id']; }

if(isset($_POST['btnSubmit'])) {
	$errmsg = array();
	$fname = $dbInst->checkStr($_POST['fname']);
	if(empty($fname)) { $errmsg[] = '- име'; }
	$sname = $dbInst->checkStr($_POST['sname']);
	$lname = $dbInst->checkStr($_POST['lname']);
	$email = $dbInst->checkStr($_POST['email']);
	if(empty($email)) { $errmsg[] = '- e-mail'; }
	else {
		$rows = $dbInst->query("SELECT * FROM `acc_users` WHERE `email` = '$email'".(($id)?" AND `id` != $id":''));
		if(is_array($rows) && count($rows)) {
			$row = $rows[0];
			$errmsg[] = '- e-mail-ът "'.$email.'" вече се ползва от друг(и) потребител(и)';
		}
	}
	$password = $dbInst->checkStr($_POST['password']);
	$password2 = $dbInst->checkStr($_POST['password2']);
	if(empty($password)) { $errmsg[] = '- парола'; }
	elseif ($password != $password2) { $errmsg[] = '- повторете паролата!'; }
	if(count($errmsg)) {
		setFlash('Моля, въведете следните данни: <br />'.implode('<br />', $errmsg));
		header('Location: '.basename($_SERVER['PHP_SELF']).(($id)?'?id='.$id:''));
		exit();
	}

	$access_level = (in_array(mb_strtolower($email), array('mars2001', 'mars', 'admin', 'plamen'))) ? 1 : 3;

	if(!$id) {
		$query = "INSERT INTO `acc_users` (`fname`, `sname`, `lname`, `email`, `password`, `access_level`, `added_on`, `updated_on`) VALUES ('$fname', '$sname', '$lname', '$email', '$password', '$access_level', datetime('now','localtime'), datetime('now','localtime'))";
		$id = $dbInst->query($query);
	} else {
		$query = "UPDATE `acc_users` SET `fname` = '$fname', `sname` = '$sname', `lname` = '$lname', `email` = '$email', `password` = '$password', `updated_on` = datetime('now','localtime') WHERE `id` = $id";
		$dbInst->query($query);
	}

	header('Location: '.basename($_SERVER['PHP_SELF']).(($id)?'?id='.$id:''));
	exit();
}

$rows = $dbInst->query("SELECT * FROM `acc_users` WHERE `id` = $id");
if(is_array($rows) && 1 == count($rows)) {
	$row = $rows[0];
}

$echoJS = <<<EOT
<script type="text/javascript">
//<![CDATA[
function unmaskPwd() {
	var value = $("input#password").val();
	var type = "text";
	if("password" == document.getElementById('password').type.toLowerCase()) {
		type = "text";
		$("a#lnkUnmask").html("скрий");
	} else {
		type = "password";
		$("a#lnkUnmask").html("покажи");
	}
	$("span#spanPwd").html('<input type="'+type+'" id="password" name="password" value="'+value+'" size="24" maxlength="70" style="width:170px;" \/>');
}
function validate(form) {
	if(!validateNotEmpty(form.fname.value)) {
		alert("Моля, въведете име.");
		form.fname.focus();
		return false;
	}
	if(!validateNotEmpty(form.email.value)) {
		alert("Моля, въведете e-mail.");
		form.email.focus();
		return false;
	}
	if(!validateNotEmpty(form.password.value)) {
		alert("Моля, въведете парола.");
		form.password.focus();
		return false;
	}
	if(form.password.value != form.password2.value) {
		alert("Моля, повторете паролата!");
		form.password2.focus();
		return false;
	}
}
//]]>
</script>
EOT;


$ptitle = SITE_NAME .' : Администрация : '.(($id != $_SESSION['sess_id'])?((isset($row))?'Акаунтът на '.HTMLFormat(trim($row['fname'].' '.$row['lname'])):'Нов акаунт'):'Моят акаунт');
include("acc_header.php");
?>
<?php if(in_array($_SESSION['sess_access_level'], array(1))) { ?>
<div class="breadcrumbs"><a href="acc_accounts_list.php">Списък на акаунтите</a> &raquo; <?=((isset($row))?'Акаунтът на '.HTMLFormat(trim($row['fname'].' '.$row['lname'])):'Нов акаунт')?></div>
<?php } ?>

  <form id="frmAccount" name="frmAccount" action="<?=basename($_SERVER['PHP_SELF']).(($id)?'?id='.$id:'')?>" method="post" onsubmit="return validate(this);">
    <div class="divider1">&nbsp;</div>
      <h3><?=(($id != $_SESSION['sess_id'])?((isset($row))?'Акаунтът на '.HTMLFormat(trim($row['fname'].' '.$row['lname'])):'Нов акаунт'):'Моят акаунт')?></h3>
      <table class="listing" cellpadding="4" cellspacing="0">
        <?php if('' != ($msg = getFlash())) { ?>
        <tr>
          <td colspan="2"><div class="err"><?=$msg?></div></td>
        </tr>
        <?php } ?>
        <tr>
          <td><p>Име* &nbsp; </p></td>
          <td><p>
              <input type="text" id="fname" name="fname" value="<?=((isset($row['fname']))?HTMLFormat($row['fname']):'')?>" size="48" />
            </p></td>
        </tr>
        <tr>
          <td><p>Презиме &nbsp; </p></td>
          <td><p>
              <input type="text" id="sname" name="sname" value="<?=((isset($row['sname']))?HTMLFormat($row['sname']):'')?>" size="48" />
            </p></td>
        </tr>
        <tr>
          <td><p>Фамилия &nbsp; </p></td>
          <td><p>
              <input type="text" id="lname" name="lname" value="<?=((isset($row['lname']))?HTMLFormat($row['lname']):'')?>" size="48" />
            </p></td>
        </tr>
        <tr>
          <td><p>E-mail* &nbsp; </p></td>
          <td><p>
              <input type="text" id="email" name="email" value="<?=((isset($row['email']))?HTMLFormat($row['email']):'')?>" size="24" maxlength="70" style="width:170px;" />
            </p></td>
        </tr>
        <tr>
          <td><p>Парола* &nbsp; </p></td>
          <td><p> <span id="spanPwd">
              <input type="password" id="password" name="password" value="<?=((isset($row['password']))?HTMLFormat($row['password']):'')?>" size="24" maxlength="70" style="width:170px;" />
              </span> &nbsp;&nbsp;<a id="lnkUnmask" href="javascript:void(null);" onclick="unmaskPwd();return false;">покажи</a></p></td>
        </tr>
        <tr>
          <td><p>Повтори паролата* &nbsp; </p></td>
          <td><p>
              <input type="password" id="password2" name="password2" value="<?=((isset($row['password']))?HTMLFormat($row['password']):'')?>" size="24" maxlength="70" style="width:170px;" />
            </p></td>
        </tr>
        <tr>
          <td><p>&nbsp; </p></td>
          <td nowrap="nowrap"><p>
              <input type="submit" id="btnSubmit" name="btnSubmit" value="Съхрани" />
            </p></td>
        </tr>
      </table>
    <div class="divider1">&nbsp;</div>
  </form>

<?php include("acc_footer.php"); ?>