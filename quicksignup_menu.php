<?php
if (!defined('e107_INIT')) { exit; }

if(isset($_POST['adduser']) && !isset($_POST['e-token'])){
	unset($_POST['e-token']);
}

require_once(e_HANDLER."form_handler.php");
require_once(e_HANDLER."mail.php");

$rs = new form;

$use_imagecode = ($menu_pref['quicksignup']['captcha'] && extension_loaded('gd'));

if($use_imagecode){
	include_once(e_HANDLER.'secure_img_handler.php');
	$sec_img = new secure_image;
}

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
		$message = "Invalid characters in username";
		$error = TRUE;
	}
	$displayname = trim(str_replace("&nbsp;", "", $displayname));
	if($displayname == "Anonymous"){
		$message = "That display name cannot be accepted as valid, please choose a different display name";
		$error = TRUE;
	}
	if($sql->db_Select("user", "*", "user_name='".$displayname."' ")){
		$message = "That display name already exists in the database, please choose a different display name";
		$error = TRUE;
	}
	if($sql->db_Select("user", "user_loginname", "user_loginname='".$loginname."' ")){
		$message = "That login name already exists in the database, please choose a different login name";
		$error = TRUE;
	}
	if($_POST['password1'] != $_POST['password2']){
		$message = "The two passwords do not match";
		$error = TRUE;
	}

	if(check_class($pref['displayname_class'])){
		if($displayname == "" || $loginname == "" || $_POST['password1'] == "" || $_POST['password2'] == ""){
			$message = "You left required field(s) blank";
			$error = TRUE;
		}
	}else{
		if($loginname == "" || $_POST['password1'] == "" || $_POST['password2'] == ""){
			$message = "You left required field(s) blank";
			$error = TRUE;
		}
	}

	if(trim($_POST['security_total']) != $srt){
		$message = "You can't add or you're a robot!";
		$error = TRUE;
	}

	if(!varset($pref['disable_emailcheck'], FALSE)){
		if(!check_email($_POST['email'])){
			$message = "That doesn't appear to be a valid email address";
			$error = TRUE;
		}else if($sql->db_Count("user", "(*)", "WHERE user_email='".$_POST['email']."' AND user_ban='1' ")){
			$message = "Email address is already used by a banned user";
			$error = TRUE;
		}else if($sql->db_Count("banlist", "(*)", "WHERE banlist_ip='".$_POST['email']."'")){
			$message = "Email address is banned";
			$error = TRUE;
		}else if($sql->db_Count("user", "(*)", "WHERE user_email='".$_POST['email']."' ")){
			$message = "Email address is already in use";
			$error = TRUE;
		}
	}

	if(!$error){
		$sql -> db_Insert("user", "0, '$displayname', '$loginname',  '', '".md5($_POST['password1'])."', '', '".$_POST['email']."', '', '', '', '1', '".time()."', '".time()."', '".time()."', '0', '0', '0', '0', '0', '0', '0', '', '', '0', '0', '', '', '', '', '".time()."', ''");

		sendemail($_POST['email'], "Your account over at ".SITENAME." has been created.", "You account at ".SITEURL." has been created with the following information:\n\nLogin Name: ".$loginname."\nPassword: ".$_POST['password']."\n\nYou should make your way over to the site and update your profile information as quickly as possible.\n\nThanks for your interest in our website.\n\n".SITENAME." Staff");

		$message = "Your account has been created. We have sent a message to your email address with your account information.";
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
		<td style='width:30%'>Display Name:</td>
		<td style='width:70%'>
		".$rs->form_text("name", 20, "", varset($pref['displayname_maxlength'],15))."
		</td>
		</tr>";
	}

	$text .= "<tr>
	<td style='width:30%'>Username:</td>
	<td style='width:70%'>
	".$rs->form_text("loginname", 20, "", varset($pref['loginname_maxlength'],30))."
	</td>
	</tr>
	<tr>
	<td style='width:30%'>Password:</td>
	<td style='width:70%'>
	".$rs->form_password("password1", 20, "", 20)."
	</td>
	</tr>
	<tr>
	<td style='width:30%'>Re-type Password:</td>
	<td style='width:70%'>
	".$rs->form_password("password2", 20, "", 20)."
	</td>
	</tr>
	<tr>
	<td style='width:30%'>Email Address:</td>
	<td style='width:70%'>
	".$rs->form_text("email", 20, "", 100)."
	</td>
	</tr>
	<tr>
	<td style='width:30%'>".$srn1." + ".$srn2." =</td>
	<td style='width:70%'>
	".$rs->form_text("security_total", 20, "", 20)."
	</td>
	</tr>";
	if($use_imagecode){
		$text .= "
		<tr>
		<td colspan='2'>
		<input type='hidden' name='rand_num' value='".$sec_img->random_number."' />
		".$sec_img->r_image()."
		<br />
		".$rs->form_text("code_verify", 20, "", 20)."
		</td>
		</tr>";
	}
	$text .= "
	<tr style='vertical-align:top'>
	<td colspan='2' style='text-align:center'>
	<input class='button' type='submit' name='adduser' value='Sign up!' />
	<input type='hidden' name='srt' value='".$srn1."/".$srn2."' />
	<input type='hidden' name='e-token' value='".e_TOKEN."' />
	</td>
	</tr>
	</table>
	</form>
	</div>";

	$ns->tablerender("Quick Signup", $text, 'quicksignup');
}

?>