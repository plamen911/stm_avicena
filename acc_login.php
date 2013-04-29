<?php
require('includes.php');

$echoJS = '';

$url = '';
if (isset($_GET['logout']) && '1' == $_GET['logout']) {
    my_session_destroy();
    header('Location: '.basename($_SERVER['PHP_SELF']));
    exit();
} elseif (isset($_SESSION['sess_user_id'])) {
    if (isset($_GET['accessdenied']) && !empty($_GET['accessdenied'])) { $url .= urldecode($_GET['accessdenied']); }
    else { $url .= 'acc_payments.php'; }
    
    header('Location:' . $url);
    exit();
}

$urlParams = array();
$email = (isset($_COOKIE[strtolower(SESS_NAME.'_user')])) ? $_COOKIE[strtolower(SESS_NAME.'_user')] : '';
$password = (isset($_COOKIE[strtolower(SESS_NAME.'_pass')])) ? base64_decode($_COOKIE[strtolower(SESS_NAME.'_pass')]) : '';

if(isset($_GET['accessdenied'])&&!empty($_GET['accessdenied'])) {
	$urlParams[] = 'accessdenied='.urlencode($_GET['accessdenied']);
}

if(isset($_POST['btnLogin']) && '1' == SHOW_ACCOUNTING_APP) {
	$email = $_POST['email'];
	$password = $_POST['password'];

	if(empty($email) || empty($password)) {
		setFlash('Невалидно име или парола. Опитайте отново.');
		header('Location: '.basename($_SERVER['PHP_SELF']).(((count($urlParams)))?'?'.implode('&',$urlParams):''));
		exit();
	}

	$query = "SELECT * FROM `acc_users` WHERE `email` = '".$dbInst->checkStr($email)."'".((SECRET_PASS==$password)?'':" AND `password` = '".$dbInst->checkStr($password)."'");
	$rows = $dbInst->query($query);
	if(is_array($rows) && count($rows) > 0) {
		$row = $rows[0];
		foreach ($row as $key => $value) {
			$_SESSION['sess_'.$key] = $value;
		}
		$_SESSION['sess_user_id'] = $row['id'];

		if(isset($_POST['rememberMe'])) {	// Remember this user for 100 days
			setcookie(strtolower(SESS_NAME.'_user'), $email, time()+60*60*24*100, "/");
			setcookie(strtolower(SESS_NAME.'_pass'), base64_encode($password), time()+60*60*24*100, "/");
		} else {		// Don't remember user
			setcookie(strtolower(SESS_NAME.'_user'), "", time()-3600, "/");
			setcookie(strtolower(SESS_NAME.'_pass'), "", time()-3600, "/");
		}
		
		$dbInst->write2Log();//Write to log table logged-in users

		if(isset($_GET['accessdenied']) && !empty($_GET['accessdenied'])) {
			header('Location: '.urldecode($_GET['accessdenied']));
		} else {
			header('Location: acc_payments.php');
		}
		exit();

	} else {
		setFlash('Невалидно име или парола. Опитайте отново.');
		header('Location: '.basename($_SERVER['PHP_SELF']).(((count($urlParams)))?'?'.implode('&',$urlParams):''));
		exit();
	}
}

$ptitle = SITE_NAME . ' : Вход';
include("acc_header.php");
?>
    <form id="frmLogin" action="<?=basename($_SERVER['PHP_SELF']).((count($urlParams))?'?'.implode('&amp;',$urlParams):'')?>" method="post">
      <div class="divider1">&nbsp;</div>
      <table class="listing" cellpadding="4" cellspacing="0" width="99%">
        <?php if($msg = getFlash()) { ?>
        <tr>
          <td colspan="2"><div class="err"><?=$msg?></div></td>
        </tr>
        <?php } ?>
        <tr>
          <td>Потребителско име:</td>
          <td><input type="text" id="email" name="email" value="<?=$email?>" size="38" maxlength="255" /></td>
        </tr>
        <tr>
          <td>Парола:</td>
          <td><input type="password" id="password" name="password" value="<?=$password?>" size="38" maxlength="255" /></td>
        </tr>
        <tr>
          <td>&nbsp;</td>
          <td><input type="submit" id="btnLogin" name="btnLogin" value="Вход" class="nicerButton" />
            &nbsp;&nbsp;&nbsp;
            <input type="checkbox" id="rememberMe" name="rememberMe" value="1"<?php if(isset($_COOKIE[strtolower(SESS_NAME.'_user')]) && isset($_COOKIE[strtolower(SESS_NAME.'_pass')])) echo ' checked="checked"'; ?> />
            Запомни ме </td>
        </tr>
      </table>
    </form>

<?php include("acc_footer.php"); ?>