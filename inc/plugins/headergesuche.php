<?php

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.");
}

//Globale Variabel (Header)
$plugins->add_hook('global_intermediate', 'header_wanted');
//Newthread Hooks
$plugins->add_hook("newthread_start", "newthread_wanted");
$plugins->add_hook("newthread_do_newthread_end", "newthread_wanted_do");
//Edit Hooks
$plugins->add_hook("editpost_end", "editpost_wanted");
$plugins->add_hook("editpost_do_editpost_end", "editpost_wanted_do");
//Mod CP
$plugins->add_hook('modcp_start', 'modcp_wantedoverview');
$plugins->add_hook('modcp_nav', 'modcp_wantedoverview_nav');
//Showthread
$plugins->add_hook('showthread_start', 'showthread_wanted');
//Forumdisplay
$plugins->add_hook('forumdisplay_thread_end', 'forumdisplay_wanted');
//Profil
$plugins->add_hook('member_profile_end', 'profile_wanted');
//Alle Gesuche anzeigen auf einer extra Seite
$plugins->add_hook('misc_start', 'showall_wanted');

function headergesuche_info()
{
    return array(
        "name"			=> "Gesuche im Header",
        "description"	=> "Erzeugt einen Drop Down im Header, sowie kann man eintragen, wann ein Gesuche gepostet wurde.",
        "website"		=> "",
        "author"		=> "Ales",
        "authorsite"	=> "",
        "version"		=> "1.0",
        "guid" 			=> "",
        "codename"		=> "",
        "compatibility" => "*"
    );
}

function headergesuche_install()
{
    global $db, $mybb;

    $db->query("ALTER TABLE `".TABLE_PREFIX."threads` ADD `csb` varchar(400) CHARACTER SET utf8;");
    $db->query("ALTER TABLE `".TABLE_PREFIX."threads` ADD `sg` varchar(400) CHARACTER SET utf8;");
    $db->query("ALTER TABLE `".TABLE_PREFIX."threads` ADD `wanted_age` varchar(400) CHARACTER SET utf8;");
    $db->query("ALTER TABLE `".TABLE_PREFIX."threads` ADD `wanted_blood` varchar(400) CHARACTER SET utf8;");
    $db->query("ALTER TABLE `".TABLE_PREFIX."threads` ADD `wanted_rela` varchar(400) CHARACTER SET utf8;");
    $db->query("ALTER TABLE `".TABLE_PREFIX."threads` ADD `wanted_work` varchar(400) CHARACTER SET utf8;");
    $db->query("ALTER TABLE `".TABLE_PREFIX."threads` ADD `wanted_ava` varchar(400) CHARACTER SET utf8;");

    $setting_group = array(
        'name' => 'headergesuche',
        'title' => 'Gesuche',
        'description' => 'Hier befinden sich alle Einstellungen für die Gesuche im Header, sowie die Verwaltung.',
        'disporder' => 2,
        'isdefault' => 0
    );

    $gid = $db->insert_query ("settinggroups", $setting_group);

    $setting_array = array(
        'name' => 'wanted_cat',
        'title' => 'Gesuchskategorie',
        'description' => 'Hier kannst du den FID angeben, welcher zu den Gesuchen führt.',
        'optionscode' => 'text',
        'value' => '1',
        'disporder' => 1,
        "gid" => (int)$gid
    );
    $db->insert_query ('settings', $setting_array);



    $setting_array = array(
        'name' => 'wanted_length',
        'title' => 'Gesuchslänge',
        'description' => 'Wie lange soll der Thementitel sein?',
        'optionscode' => 'text',
        'value' => '20',
        'disporder' => 5,
        "gid" => (int)$gid
    );
    $db->insert_query ('settings', $setting_array);
    rebuild_settings();
}

function headergesuche_is_installed()
{
    global $db, $mybb;

    if($db->field_exists("csb", "threads"))
    {
        return true;
    }
    return false;
}

function headergesuche_uninstall()
{
    global $db;
    $db->query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE name='headergesuche'");
    $db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='wanted_cat'");
    $db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='wanted_length'");
    //threadstabelle
    if($db->field_exists("csb", "threads"))
    {
        $db->drop_column("threads", "csb");
    }

    if($db->field_exists("sg", "threads"))
    {
        $db->drop_column("threads", "sg");
    }

    if($db->field_exists("wanted_age", "threads"))
    {
        $db->drop_column("threads", "wanted_age");
    }
    if($db->field_exists("wanted_blood", "threads"))
    {
        $db->drop_column("threads", "wanted_blood");
    }
    if($db->field_exists("wanted_rela", "threads"))
    {
        $db->drop_column("threads", "wanted_rela");
    }
    if($db->field_exists("wanted_work", "threads"))
    {
        $db->drop_column("threads", "wanted_work");
    }
    if($db->field_exists("wanted_ava", "threads"))
    {
        $db->drop_column("threads", "wanted_ava");
    }
    rebuild_settings();
}

function headergesuche_activate()
{
    global $db, $mybb;


    $insert_array = array(
        'title' => 'wanted_bit_header',
        'template' => $db->escape_string('	<option value="forumdisplay.php?fid={$forum}" style="font-weight:bold; letter-spacing:2px;">» {$wanted_forum}</option>
{$wanted_bit}'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'wanted_bit_misc',
        'template' => $db->escape_string('<div class="wanted_box" style="height: 180px; width: 48%;">
	<div class="wanted_headline">{$wanted_link}</div>
	<div class="wanted_forum">{$wanted_user}</div>
	<div>
	<table style="margin: auto; width: 98%;"><tr><td width="50%">
	<div class="wanted_infos" style="padding: 0 2px 2px 0;"><i class="fas fa-birthday-cake"></i> {$age}</div></td><td width="50%">  	<div class="wanted_infos" style="padding: 0 0 2px 0;"><i class="fas fa-tint"></i> {$blood}</td></tr>
	<tr><td><div class="wanted_infos"  style="padding: 0 2px 2px 0;"><i class="fas fa-heart"></i> {$rela}</div></td><td><div class="wanted_infos"  style="padding: 0 0 2px 0;"> <i class="fas fa-briefcase"></i> {$work}</div></td></tr>
	<tr><td colspan="2"><div class="wanted_infos"><i class="fas fa-portrait"></i> {$ava}</div></td></tr>
		</table></div>
</div>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'wanted_bit_modcp',
        'template' => $db->escape_string('<tr><td class="thead" colspan="3"><h1>{$wanted_forum}</h1></td></tr>
{$modcp_wanted_bit}'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'wanted_header',
        'template' => $db->escape_string('<div style="text-align: left; padding-bottom: 10px; text-transform: uppercase;">
		{$wanted_link}
{$wanted_user}
	<table style="margin: auto; width: 90%;"><tr><td width="50%">
	<div class="smalltext" style="padding: 0 2px 2px 0;"><i class="fas fa-birthday-cake"></i> {$age}</div></td><td width="50%">  	<div class="smalltext" style="padding: 0 0 2px 0;"><i class="fas fa-tint"></i> {$blood}</td></tr>
	<tr><td><div class="smalltext"  style="padding: 0 2px 2px 0;"><i class="fas fa-heart"></i> {$rela}</div></td><td><div class="smalltext"  style="padding: 0 0 2px 0;"> <i class="fas fa-briefcase"></i> {$work}</div></td></tr>
	<tr><td colspan="2"><div class="smalltext"><i class="fas fa-portrait"></i> {$ava}</div></td></tr>
	</table>
</div>
<form name="gesuch">
<select name="link" SIZE="1" onChange="window.location.href = document.gesuch.link.options[document.gesuch.link.selectedIndex].value;" class="gesuche"><option selected value="#" style="font-style: italic; letter-spacing:2px;">» Weitere Gesuche</option>
{$wanted_dropdown}
</select>
</form>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'wanted_header_bit',
        'template' => $db->escape_string('<option value=\'showthread.php?tid={$tid}\'>{$prefix} {$subject}</option>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'wanted_menu',
        'template' => $db->escape_string('<td width="180px" valign="top">
	<table class="tborder">
		<tr><td class="thead">Menü</td></tr>
		<tr><td class="trow2"><a href="misc.php?action=wanted">Internegesuche anzeigen</a></td></tr>		
		<tr><td class="trow1"><a href="misc.php?action=wantedadd">Internes Gesuch einreichen</a></td></tr>
		<tr><td class="trow1"><a href="misc.php?action=wantedshowown">eigene Gesuche anzeigen</a></td></tr>
	</table>
</td>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'wanted_misc',
        'template' => $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - Alle Gesuche</title>
{$headerinclude}
</head>
<body>
{$header}
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead"><div class="headline">Alle Gesuche</div></td>
</tr>
<tr>
<td class="trow1" align="center">
{$wanted_cat}
</td>
</tr>
</table>
{$footer}
</body>
</html>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);


    $insert_array = array(
        'title' => 'wanted_misc_bit',
        'template' => $db->escape_string('<table width="100%">
	<tr><td class="thead"><h1>{$forumname}</h1></td></tr>
	<tr><td>
		{$wanted_info_bit}
		</td></tr>
</table>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'wanted_modcp',
        'template' => $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - Gesuchübersicht</title>
{$headerinclude}
</head>
<body>
	{$header}
	<table width="100%" border="0" align="center">
		<tr>
		{$modcp_nav}
			<td valign="top">
			<table width="100%" class="trow1">
				<tr><td><h2>Gesuche</h2></td>
					<td><h2>Character Search Board</h2></td>
					<td><h2>Storming Gates</h2></td></tr>
			{$modcp_wanted}
				</table>
			</td>
		</tr>
	</table>
	{$footer}
</body>
</html>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'wanted_modcp_bit',
        'template' => $db->escape_string('<tr>
	<td class="trow1">
		<b>{$thread_title}</b></td>
		<td class="trow2">{$csb}
	</td>
	<td class="trow1">
		{$sg}
	</td>
</tr>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'wanted_profile_wantedinfo',
        'template' => $db->escape_string('<<div class="wanted_box">
	<div class="wanted_headline">{$wanted_link}</div>
	<div class="wanted_forum">{$wanted_user}</div>
	<div>
	<table style="margin: auto; width: 98%;"><tr><td width="50%">
	<div class="wanted_infos" style="padding: 0 2px 2px 0;"><i class="fas fa-birthday-cake"></i> {$age}</div></td><td width="50%">  	<div class="wanted_infos" style="padding: 0 0 2px 0;"><i class="fas fa-tint"></i> {$blood}</td></tr>
	<tr><td><div class="wanted_infos"  style="padding: 0 2px 2px 0;"><i class="fas fa-heart"></i> {$rela}</div></td><td><div class="wanted_infos"  style="padding: 0 0 2px 0;"> <i class="fas fa-briefcase"></i> {$work}</div></td></tr>
	<tr><td colspan="2"><div class="wanted_infos"><i class="fas fa-portrait"></i> {$ava}</div></td></tr>
		</table></div>
</div>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'wanted_showthread_wantedinfo',
        'template' => $db->escape_string('<tr><td style="padding-top: 5px;"><table width="100%">
	<tr><td class="tcat"><h2><i class="fas fa-birthday-cake"></i> Alter</h2></td>
		<td class="tcat"><h2><i class="fas fa-tint"></i> Blutstatus</h2></td>
		<td class="tcat"><h2><i class="fas fa-heart"></i> Beziehung</h2></td>
		<td class="tcat"><h2><i class="fas fa-briefcase"></i> Arbeit/Haus</h2></td>
		<td class="tcat"><h2><i class="fas fa-portrait"></i> Avatarvorschlag</h2></td>
	</tr>
	<tr>
		<td class="trow1" width="20%"><div class="wanted_info">{$age}</div></td>
			<td class="trow1" width="20%"><div class="wanted_info">{$blood}</div></td>
		<td class="trow2" width="20%"><div class="wanted_info">{$rela}</div></td>
		<td class="trow1" width="20%"><div class="wanted_info">{$work}</div></td>
		<td class="trow2" width="20%"><div class="wanted_info">{$ava}</div></td>
	</tr>
	</table></td></tr>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'wanted_thread',
        'template' => $db->escape_string('<tr>
<td class="trow1" width="20%"><strong>Alter des Gesuchten:</strong>
	<div class="smalltext">Welches Alter/Altersspanne haben die/der Gesuchte?</div></td>
<td class="trow1"><span class="smalltext"><input type="text" class="textbox" name="wanted_age" size="40" maxlength="155" value="{$wanted_age}" /> </td>
</tr>
<tr>
<td class="trow1" width="20%"><strong>Blutstatus des Charakters:</strong>
	<div class="smalltext">Ist der Charakter Rein-, Halbblütig, Muggelstämmig oder sogar nur Muggel??</div></td>
<td class="trow1"><span class="smalltext"><input type="text" class="textbox" name="wanted_blood" size="40" maxlength="155" value="{$wanted_blood}" /> </td>
</tr>
<tr>
<td class="trow1" width="20%"><strong>Beziehung der Charaktere:</strong>
	<div class="smalltext">Inwelcher Beziehung stehen die Charaktere zueinander?</div></td>
<td class="trow1"><span class="smalltext"><input type="text" class="textbox" name="wanted_rela" size="40" maxlength="155" value="{$wanted_rela}" /> </td>
</tr>
<tr>
<td class="trow1" width="20%"><strong>Arbeit/Hogwartshaus:</strong>
	<div class="smalltext">Was Arbeitet der Charakter bzw. in welches Haus geht er?</div></td>
<td class="trow1"><span class="smalltext"><input type="text" class="textbox" name="wanted_work" size="40" maxlength="155" value="{$wanted_work}" /> </td>
</tr>
<tr>
<td class="trow1" width="20%"><strong>Avatarvorschläge:</strong>
	<div class="smalltext">Welche Avatarvorschläge gibt es?</div></td>
<td class="trow1"><span class="smalltext"><input type="text" class="textbox" name="wanted_ava" size="40" maxlength="155" value="{$wanted_ava}" /> </td>
</tr>
<tr>
<td class="trow1" width="20%"><strong>Storming Gates:</strong>
	<div class="smalltext">Wann wurde das Gesuche ins SG eingetragen?</div></td>
<td class="trow1"><span class="smalltext"><input type="text" class="textbox" name="sg" size="40" maxlength="155" value="{$sg}" {$readonly} /> </td>
</tr>
<tr>
<td class="trow1" width="20%"><strong>CSB:</strong>
	<div class="smalltext">Wann wurde das Gesuche ins CSB eingetragen?</div></td>
<td class="trow1"><span class="smalltext"><input type="text" class="textbox" name="csb" size="40" maxlength="155" value="{$csb}" {$readonly} /> </td>
</tr>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    require MYBB_ROOT."/inc/adminfunctions_templates.php";
    find_replace_templatesets("header", "#".preg_quote('{$pm_notice}')."#i", '{$header_wanted}{$pm_notice}');
    find_replace_templatesets("newthread", "#".preg_quote('{$posticons}')."#i", '{$wanted_thread}{$posticons}');
    find_replace_templatesets("editpost", "#".preg_quote('{$posticons}')."#i", '{$wanted_thread}{$posticons}');
    find_replace_templatesets("forumdisplay_thread", "#".preg_quote('{$thread[\'profilelink\']}')."#i", '{$wanted_forumdisplay}{$thread[\'profilelink\']}');
    find_replace_templatesets("modcp_nav_users", "#".preg_quote('{$nav_editprofile}')."#i", '{$nav_editprofile}{$wantedoverview}');
    find_replace_templatesets("showthread", "#".preg_quote('{$thread[\'subject\']}</strong>
				</div>')."#i", '}{$thread[\'subject\']}</strong>
				</div>	 {$wanted_showthread}');

    find_replace_templatesets("showthread", "#".preg_quote('<tr><td id="posts_container">')."#i", '{$wanted_infos} <tr><td id="posts_container">');
    find_replace_templatesets("member_profile", "#".preg_quote('{$modoptions}')."#i", '{$wanted_profile}{$modoptions}');
}

function headergesuche_deactivate()
{
    global $db;
    $db->delete_query("templates", "title IN ('wanted_header', 'wanted_bit_header', 'wanted_header_bit', 'wanted_modcp', 'wanted_bit_modcp', 'wanted_modcp_bit', 'wanted_thread')");
    require MYBB_ROOT."/inc/adminfunctions_templates.php";
    find_replace_templatesets("header", "#".preg_quote('{$header_wanted}')."#i", '', 0);
    find_replace_templatesets("newthread", "#".preg_quote('{$wanted_thread}')."#i", '', 0);
    find_replace_templatesets("editpost", "#".preg_quote('{$wanted_thread}')."#i", '', 0);
    find_replace_templatesets("forumdisplay_thread", "#".preg_quote('{$wanted_forumdisplay}')."#i", '', 0);
    find_replace_templatesets("modcp_nav_users", "#".preg_quote('{$wantedoverview}')."#i", '', 0);
    find_replace_templatesets("showthread", "#".preg_quote('}{$thread[\'subject\']}</strong>
				</div>	 {$wanted_showthread}')."#i", '}{$thread[\'subject\']}</strong>
				</div>');
    find_replace_templatesets("showthread", "#".preg_quote('{$wanted_infos}')."#i", '', 0);
    find_replace_templatesets("member_profile", "#".preg_quote('{$wanted_profile}')."#i", '', 0);
}



//Hier ist der Spaß, wenn man ein neues Thread erstellt, dann soll das doch bitte angezeigt werden. Es wird nur dem Team angezeigt.
function newthread_wanted(){
    global $mybb, $db, $templates, $forum, $post_errors, $thread, $wanted_thread;
    //Settings ziehen
    $wanted = $mybb->settings['wanted_cat'];

    $forum['parentlist'] = ",".$forum['parentlist'].",";
    if(preg_match("/,$wanted,/i", $forum['parentlist'])) {

        $pid = $mybb->get_input ('pid', MyBB::INPUT_INT);
        if ($thread['firstpost'] == $pid) {
            if(isset($mybb->input['previewpost']) || $post_errors)
            {
                $csb = htmlspecialchars($mybb->get_input('csb'));
                $sg= htmlspecialchars($mybb->get_input('sg'));
                $wanted_age = htmlspecialchars($mybb->get_input('wanted_age'));
                $wanted_rela= htmlspecialchars($mybb->get_input('wanted_rela'));
                $wanted_work = htmlspecialchars($mybb->get_input('wanted_work'));
                $wanted_ava= htmlspecialchars($mybb->get_input('wanted_ava'));
                $wanted_blood= htmlspecialchars($mybb->get_input('wanted__blood'));
            } else{
                $csb = $thread['csb'];
                $sg = $thread['month'];
                $wanted_age = $thread['wanted_age'];
                $wanted_rela= $thread['wanted_rela'];
                $wanted_work = $thread['wanted_work'];
                $wanted_ava= $thread['wanted_ava'];
                $wanted_blood= $thread['wanted_blood'];
            }
            if($mybb->usergroup['canmodcp'] != '1') {
                $readonly = "readonly";
            }

            eval("\$wanted_thread = \"" . $templates->get("wanted_thread") . "\";");


        }
    }
}


function newthread_wanted_do(){
    global $db, $mybb, $templates, $tid, $forum, $thread;

    $wanted = $mybb->settings['wanted_cat'];

    $forum['parentlist'] = ",".$forum['parentlist'].",";
    if(preg_match("/,$wanted,/i", $forum['parentlist'])) {
        $csb = $_POST['csb'];
        $sg = $_POST['sg'];
        $wanted_age = $_POST['wanted_age'];
        $wanted_rela = $_POST['wanted_rela'];
        $wanted_work = $_POST['wanted_work'];
        $wanted_ava = $_POST['wanted_ava'];
        $wanted_blood = $_POST['wanted_blood'];
        $new_array = array(
            "csb" => $db->escape_string($csb),
            "sg" => $db->escape_string($sg),
            "wanted_age" => $db->escape_string($wanted_age),
            "wanted_rela" => $db->escape_string($wanted_rela),
            "wanted_work" => $db->escape_string($wanted_work),
            "wanted_blood" => $db->escape_string($wanted_blood),
            "wanted_ava" => $db->escape_string($wanted_ava)
        );

        $db->update_query("threads", $new_array, "tid='{$tid}'");
    }

}


// Auch Edit sollen die zwei Felder angezeigt werden.

function editpost_wanted(){
    global $mybb, $forum, $templates, $db,$post_errors, $thread, $wanted_thread, $readonly ;

//Zieht sich erstmal die Einstellung
    $wanted = $mybb->settings['wanted_cat'];

    $forum['parentlist'] = ",".$forum['parentlist'].",";
    if(preg_match("/,$wanted,/i", $forum['parentlist'])) {
        $pid = $mybb->get_input ('pid', MyBB::INPUT_INT);
        if ($thread['firstpost'] == $pid) {
            if(isset($mybb->input['previewpost']) || $post_errors)
            {
                $csb = htmlspecialchars($mybb->get_input('csb'));
                $sg = htmlspecialchars($mybb->get_input('sg'));
                $wanted_age = htmlspecialchars($mybb->get_input('wanted_age'));
                $wanted_rela= htmlspecialchars($mybb->get_input('wanted_rela'));
                $wanted_work = htmlspecialchars($mybb->get_input('wanted_work'));
                $wanted_blood = htmlspecialchars($mybb->get_input('wanted_blood'));
                $wanted_ava= htmlspecialchars($mybb->get_input('wanted_ava'));
            } else{
                $csb = $thread['csb'];
                $sg = $thread['sg'];
                $wanted_age = $thread['wanted_age'];
                $wanted_rela= $thread['wanted_rela'];
                $wanted_work = $thread['wanted_work'];
                $wanted_blood = $thread['wanted_blood'];
                $wanted_ava= $thread['wanted_ava'];
            }

            if($mybb->usergroup['canmodcp'] != '1') {
                $readonly = "readonly";
            }
            eval("\$wanted_thread = \"" . $templates->get("wanted_thread") . "\";");


        }
    }
}

//und jetzt mach, was du machen sollst
function editpost_wanted_do(){
    global $db, $mybb, $templates, $tid, $forum, $thread;

    $wanted = $mybb->settings['wanted_cat'];

    $forum['parentlist'] = ",".$forum['parentlist'].",";
    if(preg_match("/,$wanted,/i", $forum['parentlist'])) {
        $pid = $mybb->get_input ('pid', MyBB::INPUT_INT);
        if ($thread['firstpost'] == $pid) {

            $csb = $mybb->input['csb'];
            $sg = $mybb->input['sg'];
            $wanted_age = $mybb->input['wanted_age'];
            $wanted_rela = $mybb->input['wanted_rela'];
            $wanted_work = $mybb->input['wanted_work'];
            $wanted_ava = $mybb->input['wanted_ava'];
            $wanted_blood = $mybb->input['wanted_blood'];

            $new_array = array(
                "csb" => $db->escape_string($csb),
                "sg" => $db->escape_string($sg),
                "wanted_age" => $db->escape_string($wanted_age),
                "wanted_rela" => $db->escape_string($wanted_rela),
                "wanted_work" => $db->escape_string($wanted_work),
                "wanted_blood" => $db->escape_string($wanted_blood),
                "wanted_ava" => $db->escape_string($wanted_ava)
            );

            $db->update_query("threads", $new_array, "tid='{$tid}'");
        }
    }
}

function header_wanted(){
    global $db, $mybb, $female, $templates, $header_wanted, $wanted_bit, $wanted_dropdown, $wanted_showthread;

    $length = $mybb->settings['wanted_length'];
    $wanted_cat = $mybb->settings['wanted_cat'];


    $query = $db->query("SELECT *  
    FROM ".TABLE_PREFIX."posts p 
    LEFT JOIN ".TABLE_PREFIX."threads t 
    ON (p.tid=t.tid) 
    LEFT JOIN ".TABLE_PREFIX."forums f 
    ON (p.fid=f.fid) 
    LEFT JOIN ".TABLE_PREFIX."threadprefixes tp 
    ON (tp.pid=t.prefix) 
    LEFT JOIN ".TABLE_PREFIX."users u
    ON (u.uid = t.uid)
    WHERE f.parentlist LIKE '".$wanted_cat.",%' 
    AND tp.prefix != '[Reserviert]'
    and t.visible = 1
    ORDER BY RAND() LIMIT 1");
    while($wanted = $db->fetch_array($query))
    {
        $wanted_link = "";
        $prefix = "";
        $subject = "";
        $tid = "";

        $prefix = $wanted['displaystyle'];
        $subject = $wanted['subject'];
        $tid = $wanted['tid'];
        $age = $wanted['wanted_age'];
        $rela = $wanted['wanted_rela'];
        $work = $wanted['wanted_work'];
        $ava = $wanted['wanted_ava'];
        $blood = $wanted['wanted_blood'];

        $username = format_name($wanted['username'], $wanted['usergroup'], $wanted['displaygroup']);
        $user = build_profile_link($username, $wanted['uid']);
        $wanted_forum = $wanted['name'];

        $wanted_user = "<div class='smalltext' style='text-transform: uppercase; font-size: 11px; text-align: center;'>gesucht von {$user} in <i>{$wanted_forum}</i></div>";


        $wanted_link = "<div class='wanted_headerlink'>{$prefix} <a href=\"showthread.php?tid={$tid}\">{$subject}</a></div>";
    }


    $select = $db->query("SELECT fid, name
  FROM ".TABLE_PREFIX."forums
  WHERE parentlist LIKE '%,".$wanted_cat.",%' 
  ");


    while($forums = $db->fetch_array($select)){
        $forum = $forums['fid'];
        $wanted_bit = "";
        $subject = "";

        $wanted_forum = $forums['name'];
        $wanted_select = $db->query("SELECT *
                FROM ".TABLE_PREFIX."threads t
                LEFT JOIN ".TABLE_PREFIX."threadprefixes tp 
                ON (tp.pid=t.prefix)
                WHERE fid = '$forum'
                AND visible = 1
                and tp.prefix != '[Reserviert]'
                ORDER BY subject ASC
            ");

        while($row = $db->fetch_array($wanted_select)){

            $prefix = $row['prefix'];
            $subject = $row['subject'];
            $title_length = strlen($row['subject']);
            $tid = $row['tid'];

            if($title_length > $length){
                $subject = my_substr($subject, 0, $length)."...";
            }

            eval("\$wanted_bit .= \"".$templates->get("wanted_header_bit")."\";");

        }

        eval("\$wanted_dropdown.= \"".$templates->get("wanted_bit_header")."\";");
    }
    eval("\$header_wanted = \"".$templates->get("wanted_header")."\";");


}

//showthread



//Mod CP
function modcp_wantedoverview(){
    global $mybb, $templates, $lang, $header, $headerinclude, $footer, $page, $db, $csb, $thread, $sg, $modcp_nav,  $wanted_forum,$thread_title,$modcp_wanted_bit ;

    if($mybb->get_input('action') == 'wantedoverview') {
        // Do something, for example I'll create a page using the hello_world_template

        // Add a breadcrumb
        add_breadcrumb('Gesuchsübersicht', "modcp.php?action=wantedoverview");

        $wanted_cat =  $mybb->settings['wanted_cat'];

        $select = $db->query("SELECT fid, name
  FROM ".TABLE_PREFIX."forums
  WHERE parentlist LIKE '".$wanted_cat.",%' 
  ");


        while($forums = $db->fetch_array($select)){
            $forum = $forums['fid'];
            $modcp_wanted_bit = "";
            $sg = "";
            $csb = "";

            $wanted_forum = $forums['name'];
            $wanted_select =$db->query("SELECT *
                FROM ".TABLE_PREFIX."threads t
                LEFT JOIN ".TABLE_PREFIX."threadprefixes tp 
                ON (tp.pid=t.prefix)
                WHERE fid = '$forum'
                AND visible = 1
                ORDER BY subject ASC
            ");


            while($wanted = $db->fetch_array($wanted_select)){

                $tid = $wanted['tid'];
                if(!empty($wanted['sg'])){
                    $sg = $wanted['sg'];
                } else {
                    $sg = "Noch nicht eingetragen";
                }

                if(!empty($wanted['csb'])){
                    $csb = $wanted['csb'];
                } else {
                    $csb = "Noch nicht eingetragen";
                }

                if($wanted['threadsolved'] != '0'){
                    $solved = '<i class="fas fa-check" title="Gesuch erledigt"></i>';
                } else {
                    $solved = '';
                }
                $prefix = $wanted['prefix'];

                $thread_title = "{$prefix} <a href='showthread.php?tid={$tid}' target='_blank'>$wanted[subject]</a> {$solved}";

                eval("\$modcp_wanted_bit .= \"".$templates->get("wanted_modcp_bit")."\";");

            }

            eval("\$modcp_wanted .= \"".$templates->get("wanted_bit_modcp")."\";");
        }


        // Using the misc_help template for the page wrapper
        eval("\$page = \"".$templates->get("wanted_modcp")."\";");
        output_page($page);
    }
}


function modcp_wantedoverview_nav(){
    global $mybb, $templates, $db, $wantedoverview;

    $wantedoverview = "<tr><td class=\"trow1 smalltext\"><a href=\"modcp.php?action=wantedoverview\" class=\"modcp_nav_item modcp_nav_editprofile\">Gesuchsübersicht</a></td></tr>";

}

//showthread
function showthread_wanted(){
    global $db, $mybb, $templates, $forum, $thread, $wanted_showthread, $wanted_infos ;

    $wanted = $mybb->settings['wanted_cat'];
    $forum['parentlist'] = ",".$forum['parentlist'].",";
    $wanted_showthread = "";
    $sg = "";
    $csb = "";
    $age = "";
    $rela = "";
    $work = "";
    $ava  = "";

    $age = $thread['wanted_age'];
    $rela = $thread['wanted_rela'];
    $work = $thread['wanted_work'];
    $blood = $thread['wanted_blood'];
    $ava = $thread['wanted_ava'];

    if(preg_match("/,$wanted,/i", $forum['parentlist'])) {
        if (!empty($thread['sg'])) {
            $sg = "<b>Storming Gates am</b> " . $thread['sg'];
        }

        if (!empty($thread['csb'])) {
            $csb = "<b>Charakter Search Board am</b> " . $thread['csb'];
        }
        if (!empty($thread['csb']) OR !empty($thread['sg'])) {
            $wanted_showthread = "<div class='smalltext'>Gesuche eingetragen: " . $csb . " " . $sg . "</div>";
        }


        eval("\$wanted_infos = \"".$templates->get("wanted_showthread_wantedinfo")."\";");

    }

}

function forumdisplay_wanted(&$thread){
    global $db, $mybb, $templates, $forum, $thread, $wanted_forumdisplay,  $foruminfo, $wanted_forumdisplay_charainfo;

    $wanted = $mybb->settings['wanted_cat'];
    $forum['parentlist'] = ",".$forum['parentlist'].",";
    $foruminfo['parentlist'] = ",".$foruminfo['parentlist'].",";


    if (!empty($thread['sg'])) {
        $thread['sg'] = "<b>SG: </b> " . $thread['sg'];
    }

    if (!empty($thread['csb'])) {
        $thread['csb'] = "<b>CSB: </b> " . $thread['csb'];
    }

    if(!empty($thread['wanted_age'])){
        $wanted_forumdisplay_charainfo = "<div class='smalltext'><i class=\"fas fa-birthday-cake\"></i> {$thread['wanted_age']} <i class=\"fas fa-tint\"></i> {$thread['wanted_blood']} <i class=\"fas fa-heart\"></i> {$thread['wanted_rela']} <i class=\"fas fa-briefcase\"></i> {$thread['wanted_work']} <i class=\"fas fa-portrait\"></i> {$thread['wanted_ava']}</div>";
    } else{
        $wanted_forumdisplay_charainfo = "";
    }

    if (!empty($thread['csb']) OR !empty($thread['sg'])) {
        $wanted_forumdisplay = "<div class='smalltext'>" .  $thread['csb'] . " " .  $thread['sg'] . "</div>";
    } else {
        $wanted_forumdisplay = "";
    }
    return $thread;


}

function profile_wanted(){
    global $db, $mybb, $templates, $memprofile, $wanted_profile;

    $wanted_cat = $mybb->settings['wanted_cat'];
    $uid = $memprofile['uid'];
    $length = $mybb->settings['wanted_length'];

    $forum_query = $db->query("SELECT *  FROM ".TABLE_PREFIX."threads t
    LEFT JOIN ".TABLE_PREFIX."forums f 
    ON (t.fid=f.fid)
        LEFT JOIN ".TABLE_PREFIX."threadprefixes tp 
    ON (tp.pid=t.prefix) 
    WHERE t.uid = '$uid' 
    And f.parentlist LIKE '".$wanted_cat.",%' 
    and t.visible = 1
    ORDER BY t.subject ASC
    ");

    while($wanted = $db->fetch_array($forum_query)){

        $wanted_link = "";
        $prefix = "";
        $subject = "";
        $tid = "";

        $prefix = $wanted['displaystyle'];
        $subject = $wanted['subject'];
        $tid = $wanted['tid'];
        $age = $wanted['wanted_age'];
        $rela = $wanted['wanted_rela'];
        $work = $wanted['wanted_work'];
        $ava = $wanted['wanted_ava'];
        $blood = $wanted['wanted_blood'];
        $title_length = strlen($wanted['subject']);
        $wanted_forum = $wanted['name'];

        if($title_length > $length){
            $subject = my_substr($subject, 0, $length)."...";
        }


        $wanted_user = "<div class='smalltext' style='text-transform: uppercase; font-size: 11px; text-align: center;'>gesucht in <i>{$wanted_forum}</i></div>";


        $wanted_link = "<div class='wanted_headerlink'>{$prefix} <a href=\"showthread.php?tid={$tid}\">{$subject}</a></div>";

        eval("\$wanted_profile .= \"".$templates->get("wanted_profile_wantedinfo")."\";");

    }


}

function showall_wanted(){
    global $mybb, $templates, $lang, $header, $headerinclude, $footer, $page, $db, $forumname;

    if($mybb->get_input('action') == 'showallwanted')
    {
        // Do something, for example I'll create a page using the hello_world_template

        // Add a breadcrumb
        add_breadcrumb('Alle Gesuche anzeigen', "misc.php?action=showallwanted");


        //Dann ziehen wir uns mal alle Foren
        $length = $mybb->settings['wanted_length'];
        $wanted_forum_id = $mybb->settings['wanted_cat'];

        $select = $db->query("SELECT fid, name
  FROM ".TABLE_PREFIX."forums
  WHERE parentlist LIKE '".$wanted_forum_id.",%' 
  ");


        while($forums = $db->fetch_array($select)){
            $forum = $forums['fid'];

            echo($forum);
            $forumname = $forums['name'];
            $wanted_info_bit = "";
            //Holen wir uns die Informationen
            $query = $db->query("SELECT *  
    FROM ".TABLE_PREFIX."posts p 
    LEFT JOIN ".TABLE_PREFIX."threads t 
    ON (p.tid=t.tid) 
    LEFT JOIN ".TABLE_PREFIX."threadprefixes tp 
    ON (tp.pid=t.prefix) 
    LEFT JOIN ".TABLE_PREFIX."users u
    ON (u.uid = t.uid)
    WHERE t.fid = '$forum'
                AND t.visible = 1
                and tp.prefix != '[Reserviert]'
    ORDER BY t.subject ASC");
            while($wanted = $db->fetch_array($query))
            {
                $wanted_link = "";
                $prefix = "";
                $subject = "";
                $tid = "";

                $prefix = $wanted['displaystyle'];
                $subject = $wanted['subject'];
                $tid = $wanted['tid'];
                $age = $wanted['wanted_age'];
                $rela = $wanted['wanted_rela'];
                $work = $wanted['wanted_work'];
                $ava = $wanted['wanted_ava'];
                $blood = $wanted['wanted_blood'];

                $username = format_name($wanted['username'], $wanted['usergroup'], $wanted['displaygroup']);
                $user = build_profile_link($username, $wanted['uid']);
                $wanted_forum = $wanted['name'];

                $wanted_user = "<div class='smalltext' style='text-transform: uppercase; font-size: 11px; text-align: center;'>gesucht von {$user}</i></div>";


                $title_length = strlen($wanted['subject']);
                if($title_length > $length){
                    $subject = my_substr($subject, 0, $length)."...";
                }
                $wanted_link = "<div class='wanted_headerlink'>{$prefix} <a href=\"showthread.php?tid={$tid}\">{$subject}</a></div>";

                eval("\$wanted_info_bit .= \"".$templates->get("wanted_bit_misc")."\";");
            }

            eval("\$wanted_cat .= \"".$templates->get("wanted_misc_bit")."\";");
        }



        // Using the misc_help template for the page wrapper
        eval("\$page = \"".$templates->get("wanted_misc")."\";");
        output_page($page);
    }
}