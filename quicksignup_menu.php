<?php
if (!defined('e107_INIT')) { exit; }

if(isset($_POST['adduser']) && !isset($_POST['e-token'])){
	unset($_POST['e-token']);
}

require_once(e_HANDLER."form_handler.php");
require_once(e_HANDLER."mail.php");

$rs = new form;

$maxinteger = (($menu_pref['quicksignup']['maxinteger']) ? $menu_pref['quicksignup']['maxinteger'] : 50);

// stupidly simple security check; part 1
$srn1 = rand(1, $maxinteger);
$srn2 = rand(1, $maxinteger);

// ripped from the Quick Add User section of admin/users.php
// modified to better suit this menu item
if(isset($_POST['adduser'])){

	// sssc; part 2
	$srn = explode("/", $_POST['srt']);
	$srt = $srn[0] + $srn[1];

	$displayname = (check_class($pref['displayname_class']) ? strip_tags($_POST['name']) : strip_tags($_POST['loginname']));
	$loginname = strip_tags($_POST['loginname']);

	if(strstr($displayname, "#") || strstr($displayname, "=")){
		$message = "Ogiltiga tecken i användarnamnet";
		$error = TRUE;
	}
	$displayname = trim(str_replace("&nbsp;", "", $displayname));
	if($displayname == "Anonymous"){
		$message = "Visningsnamnet är inte giltigt, välj ett annat visningsnamn.";
		$error = TRUE;
	}
	if($sql->db_Select("user", "*", "user_name='".$displayname."' ")){
		$message = "Visningsnamnet är upptaget, välj ett annat visningsnamn.";
		$error = TRUE;
	}
	if($sql->db_Select("user", "user_loginname", "user_loginname='".$loginname."' ")){
		$message = "Användarnamnet är upptaget, välj ett annat användarnamn.";
		$error = TRUE;
	}
	if($_POST['password1'] != $_POST['password2']){
		$message = "Lösenorden matchar inte varandra";
		$error = TRUE;
	}

	if(check_class($pref['displayname_class'])){
		if($displayname == "" || $loginname == "" || $_POST['password1'] == "" || $_POST['password2'] == ""){
			$message = "Du utelämnade obligatoriskt fält";
			$error = TRUE;
		}
	}else{
		if($loginname == "" || $_POST['password1'] == "" || $_POST['password2'] == ""){
			$message = "Du utelämnade obligatoriskt fält";
			$error = TRUE;
		}
	}

	if(trim($_POST['security_total']) != $srt){
		$message = "Du kan inte addera eller så är du en bot!";
		$error = TRUE;
	}

	if(!varset($pref['disable_emailcheck'], FALSE)){
		if(!check_email($_POST['email'])){
			$message = "Epostadressen verkar inte vara giltig";
			$error = TRUE;
		}else if($sql->db_Count("user", "(*)", "WHERE user_email='".$_POST['email']."' AND user_ban='1' ")){
			$message = "Epostadressen är registrerad med en blockerad användare";
			$error = TRUE;
		}else if($sql->db_Count("banlist", "(*)", "WHERE banlist_ip='".$_POST['email']."'")){
			$message = "Epostadressen är blockerad";
			$error = TRUE;
		}else if($sql->db_Count("user", "(*)", "WHERE user_email='".$_POST['email']."' ")){
			$message = "Epostadressen används redan";
			$error = TRUE;
		}
	}

	if(!$error){
		$sql -> db_Insert("user", "0, '$displayname', '$loginname',  '', '".md5($_POST['password1'])."', '', '".$_POST['email']."', '', '', '', '1', '".time()."', '".time()."', '".time()."', '0', '0', '0', '0', '0', '0', '0', '', '', '0', '0', '', '', '', '', '".time()."', ''");

		sendemail($_POST['email'], "Ditt användarkonto på ".SITENAME." har skapats.", "Ditt konto på ".SITEURL." har skapats med följande information:\n\nInloggningasnamn: ".$loginname."\nLösenord: ".$_POST['password']."\n\nDu bör ta dig till webbplatsen för att uppdatera din profil så snart du kan.\n\nTack för visat intresse för vår webbplats.\n\n".SITENAME." Admin");

		$message = "Ditt användarkonto har registrerats och ett meddelande innehållande kontoinformationen har skickats till dig.";
	}
}

if(!USER){
	$text = "";

	if($message){
		$text .= "<div style='text-align:center;'><b>".$message."</b></div>";
	}

	$text .= "<div style='text-align:center'>
	".$rs->form_open("post", e_SELF, "adduserform")."
	<table style='90%'>
	";

	if(check_class($pref['displayname_class'])){
		$text .= "<tr>
		<td style='width:30%'>Visat namn:</td>
		<td style='width:70%'>
		".$rs->form_text("name", 20, "", varset($pref['displayname_maxlength'],15))."
		</td>
		</tr>";
	}

	$text .= "<tr>
	<td style='width:30%'>Användarnamn:</td>
	<td style='width:70%'>
	".$rs->form_text("loginname", 20, "", varset($pref['loginname_maxlength'],30))."
	</td>
	</tr>
	<tr>
	<td style='width:30%'>Lösenord:</td>
	<td style='width:70%'>
	".$rs->form_password("password1", 20, "", 20)."
	</td>
	</tr>
	<tr>
	<td style='width:30%'>Lösenordet igen:</td>
	<td style='width:70%'>
	".$rs->form_password("password2", 20, "", 20)."
	</td>
	</tr>
	<tr>
	<td style='width:30%'>Din e-post</td>
	<td style='width:70%'>
	".$rs->form_text("email", 20, "", 100)."
	</td>
	</tr>
	<tr>
	<td style='width:30%'>".$srn1." + ".$srn2." =</td>
	<td style='width:70%'>
	".$rs->form_text("security_total", 20, "", 20)."
	</td>
	</tr>
	<tr style='vertical-align:top'>
	<td colspan='2' style='text-align:center'>
	<input class='button' type='submit' name='adduser' value='Registrera!' />
	<input type='hidden' name='srt' value='".$srn1."/".$srn2."' />
	<input type='hidden' name='e-token' value='".e_TOKEN."' />
	</td>
	</tr>
	</table>
	</form>
	</div>";

	$ns->tablerender("Snabbregistrering", $text, 'quicksignup');
}

?>