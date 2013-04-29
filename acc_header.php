<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?=((isset($ptitle))?$ptitle:SITE_NAME)?></title>
<link href="acc_styles.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="js/RegExpValidate.js"></script>
<script type="text/javascript" src="js/jquery-latest.pack.js"></script>
<?php if(isset($echoJS)) echo $echoJS; ?>
<script type="text/javascript">
//<![CDATA[
function attachListHover(listName) {
	var sfEls = document.getElementById(listName).getElementsByTagName("LI");
	for(var i = 0; i < sfEls.length; i++) {
		if(sfEls[i].className == "Categrization") continue;
		else {
			sfEls[i].onmouseover = function() {
				this.className += " hover";
			};
			sfEls[i].onmouseout = function() {
				this.className = this.className.replace(new RegExp(" hover\\b"),"");
			};
		}
	}
}
//]]>
</script>
<!--[if lt IE 7]>
<script type="text/javascript">
	if (window.attachEvent) window.attachEvent("onload", function() { attachListHover("navigationbar"); } );
</script>
<![endif]-->
</head>
<body>
<div id="baseHeader">
  <div id="Nav">
    <ul id="navigationbar">
      <?php if(isset($_SESSION['sess_access_level'])) { ?>
      <?php if(in_array($_SESSION['sess_access_level'], array(1))) { ?>
      <li<?=((in_array(basename($_SERVER['PHP_SELF']),array('acc_payments.php')))?' class="selected"':'')?>> <a href="acc_payments.php">Плащания</a> </li>
      <?php } ?>
      <li<?=((in_array(basename($_SERVER['PHP_SELF']),array('acc_firms.php','acc_firm_info.php','acc_contract.php')))?' class="selected"':'')?>> <a href="acc_firms.php">Фирми</a> </li>
      <li<?=((in_array(basename($_SERVER['PHP_SELF']),array('acc_account.php', 'acc_accounts_list.php')))?' class="selected"':'')?>>
        <div class="level1"> <a>Администрация</a>
          <div class="dropdown">
            <ul>
              <?php if(in_array($_SESSION['sess_access_level'], array(1))) { ?>
              <!--<li class="categorizing"> <a>конфигуриране на системата</a>&nbsp;</li>-->
              <!--<li> <a href="#">конфигуриране на системата</a> </li>-->
              <li style="margin:5px 0 19px 0;padding-top:5px;"> <a href="acc_accounts_list.php">списък на акаунтите</a> </li>
              <?php } ?>
              <li <?=(($_SESSION['sess_access_level'] == 1)?'':' style="margin:5px 0 19px 0;padding-top:5px;"')?>> <a href="acc_account.php?id=<?=$_SESSION['sess_id']?>">моят акаунт</a> </li>
            </ul>
            <div class="shadow"> </div>
          </div>
          <div class="shim"> </div>
        </div>
      </li>
      <?php } ?>
      <li> <a href="firms.php">СТМ&nbsp;програма</a> </li>
      <?php if(isset($_SESSION['sess_access_level'])) { ?>
      <li> <a href="acc_login.php?logout=1">Изход</a> </li>
      <?php } ?>
    </ul>
  </div>
  <div id="pageContent">
