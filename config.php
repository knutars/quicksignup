<?php
$eplug_admin = TRUE;
require_once("../../class2.php");
if (!getperms("4")) { header("location:".e_BASE."index.php"); exit ;}
require_once(e_ADMIN."auth.php");

if ($_POST['update_menu']) {
	unset($menu_pref['quicksignup']);
	$menu_pref['quicksignup'] = $_POST['pref'];
	$tmp = addslashes(serialize($menu_pref));
	$sql->db_Update("core", "e107_value='$tmp' WHERE e107_name='menu_pref' ");
	$ns->tablerender("", "<div style=\'text-align:center\'><b>Inställningarna uppdaterade!</b></div>");
}

$text = "
	<div style='text-align:center'>
	<form action='".e_SELF."?".e_QUERY."' method='post'>
	<table style='width:85%' class='fborder' >

	<tr>
	<td style='width:30%' class='forumheader3'>Högsta tal att addera<br /><small>Ex 4 ger aldrig högre än 4 + 4</small></td>
	<td style='width:70%' class='forumheader3'>
	<input type='text' class='tbox' name='pref[maxinteger]' value='".(($menu_pref['quicksignup']['maxinteger']) ? $menu_pref['quicksignup']['maxinteger'] : "")."' />
	</td>
	</tr>

	<tr>
	<td colspan='2' class='forumheader' style='text-align: center;'><input class='button' type='submit' name='update_menu' value='Spara inställningarna!' /></td>
	</tr>
	</table>
	</form>
	</div>
	";

$ns->tablerender("Konfigurera snabbregistrering", $text);

require_once(e_ADMIN."footer.php");

?>