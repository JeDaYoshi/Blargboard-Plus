<?php
//  Blargboard Plus -- Profile Page

if (!defined('BLARG')) die();

$id = (int)$_REQUEST['id'];

$rUser = Query("select u.* from {users} u where u.id={0}",$id);
if(NumRows($rUser))
	$user = Fetch($rUser);
else
	Kill(__("Unknown user ID."));
	
$uname = $user['displayname'] ?: $user['name'];

$ugroup = $usergroups[$user['primarygroup']];
$usgroups = array();

$res = Query("SELECT groupid FROM {secondarygroups} WHERE userid={0}", $id);
while ($sg = Fetch($res)) $usgroups[] = $usergroups[$sg['groupid']];

if($id == $loguserid)
{
	Query("update {users} set lastprofileview={1} where id={0}", $loguserid, time());
	DismissNotification('profilecomment', $loguserid, $loguserid);
}

$canDeleteComments = ($id == $loguserid && HasPermission('user.deleteownusercomments')) || HasPermission('admin.adminusercomments');
$canComment = (HasPermission('user.postusercomments') && $user['primarygroup'] != Settings::get('bannedGroup')) || HasPermission('admin.adminusercomments');

if($loguserid && $_REQUEST['token'] == $loguser['token'])
{
	if(isset($_GET['block']))
	{
		$block = (int)$_GET['block'];
		$rBlock = Query("select * from {blockedlayouts} where user={0} and blockee={1}", $id, $loguserid);
		$isBlocked = NumRows($rBlock);
		if($block && !$isBlocked)
			$rBlock = Query("insert into {blockedlayouts} (user, blockee) values ({0}, {1})", $id, $loguserid);
		elseif(!$block && $isBlocked)
			$rBlock = Query("delete from {blockedlayouts} where user={0} and blockee={1} limit 1", $id, $loguserid);
		die(header("Location: ".actionLink("profile", $id, '', $user['name'])));
	}
	if($_GET['action'] == "delete")
	{
		$postedby = FetchResult("SELECT cid FROM {usercomments} WHERE uid={0} AND id={1}", $id, (int)$_GET['cid']);
		if ($canDeleteComments || ($postedby == $loguserid && HasPermission('user.deleteownusercomments')))
		{
			Query("delete from {usercomments} where uid={0} and id={1}", $id, (int)$_GET['cid']);
			if ($loguserid != $id)
			{
				// dismiss any new comment notification that has been sent to that user, unless there are still new comments
				$lastcmt = FetchResult("SELECT date FROM {usercomments} WHERE uid={0} ORDER BY date DESC LIMIT 1", $id);
				if ($lastcmt < $user['lastprofileview'])
					DismissNotification('profilecomment', $id, $id);
			}
			die(header("Location: ".actionLink("profile", $id, '', $user['name'])));
		}
	}

	if(isset($_POST['actionpost']) && !IsReallyEmpty($_POST['text']) && $canComment)
	{
		$rComment = Query("insert into {usercomments} (uid, cid, date, text) values ({0}, {1}, {2}, {3})", $id, $loguserid, time(), $_POST['text']);
		if($loguserid != $id)
		{
			SendNotification('profilecomment', $id, $id);
		}
		die(header("Location: ".actionLink("profile", $id, '', $user['name'])));
	}
}



if($loguserid)
{
	if (Settings::get('postLayoutType'))
	{
		$blocktext = __('Block layout');
		$unblocktext = __('Unblock layout');
	}
	else
	{
		$blocktext = __('Block signature');
		$unblocktext = __('Unblock signature');
	}
	
	$rBlock = Query("select * from {blockedlayouts} where user={0} and blockee={1}", $id, $loguserid);
	$isBlocked = NumRows($rBlock);
	if($isBlocked)
		$blockLayoutLink = actionLinkTag($unblocktext, "profile", $id, "block=0&token={$loguser['token']}");
	else
		$blockLayoutLink = actionLinkTag($blocktext, "profile", $id, "block=1&token={$loguser['token']}");
}

$daysKnown = (time()-$user['regdate'])/86400;
if (!$daysKnown) $daysKnown = 1;

$posts = FetchResult("select count(*) from {posts} where user={0}", $id);
$threads = FetchResult("select count(*) from {threads} where user={0}", $id);
$averagePosts = sprintf("%1.02f", $user['posts'] / $daysKnown);
$averageThreads = sprintf("%1.02f", $threads / $daysKnown);
$deletedposts = FetchResult("SELECT COUNT(*) FROM {posts} p WHERE p.user={0} AND p.deleted!=0 AND p.deletedby!={0}", $id);
$score = 0 + (10 * $user['postplusones']) - (20 * $deletedposts);

$badgersR = Query("select * from {badges} where owner={0} order by color", $id);
if(NumRows($badgersR))
{
	$badgers = "";
	$colors = array("bronze", "silver", "gold", "platinum");
	while($badger = Fetch($badgersR))
		$badgers .= Format("<span class=\"badge {0}\">{1}</span> ", $colors[$badger['color']], $badger['name']);
}

if($user['title'])
	$title = preg_replace('@<br.*?>\s*(\S)@i', ' &bull; $1', strip_tags(CleanUpPost($user['title'], "", true), "<b><strong><i><em><span><s><del><img><a><br><br/><small>"));

if($user['homepageurl'])
{
	$nofollow = "";
	if(Settings::get("nofollow"))
		$nofollow = "rel=\"nofollow\"";
			
	if($user['homepagename'])
		$homepage = "<a $nofollow target=\"_blank\" href=\"".htmlspecialchars($user['homepageurl'])."\">".htmlspecialchars($user['homepagename'])."</a> - ".htmlspecialchars($user['homepageurl']);
	else
		$homepage = "<a $nofollow target=\"_blank\" href=\"".htmlspecialchars($user['homepageurl'])."\">".htmlspecialchars($user['url'])."</a>";
	$homepage = securityPostFilter($homepage);
}

$emailField = __("Private");
if($user['email'] == "")
	$emailField = __("None given");
else if ($user['showemail'])
	$emailField = "<span id=\"emailField\">".__("Public")." <button style=\"font-size: 0.7em;\" onclick=\"$(this.parentNode).load('".URL_ROOT."ajaxcallbacks.php?a=em&amp;id=".$id."');\">".__("Show")."</button></span>";
else if (HasPermission('admin.editusers'))
	$emailField = "<span id=\"emailField\">".__("Private")." <button style=\"font-size: 0.7em;\" onclick=\"$(this.parentNode).load('".URL_ROOT."ajaxcallbacks.php?a=em&amp;id=".$id."');\">".__("Snoop")."</button></span>";


$profileParts = array();

$temp = array();
$temp[__("Name")] = $minipic . htmlspecialchars($user['displayname'] ? $user['displayname'] : $user['name']) . ($user['displayname'] ? " (".htmlspecialchars($user['name']).")" : "");
if($title)
	$temp[__("Status")] = $title;
	
$glist = '<strong class="userlink" style="color: '.htmlspecialchars($ugroup['color_unspec']).';">'.htmlspecialchars($ugroup['name']).'</strong>';
foreach ($usgroups as $sgroup)
{
	if ($sgroup['display'] > -1)
		$glist .= ', '.htmlspecialchars($sgroup['name']);
}
$temp[__("Power")] = $glist;

$temp[__("Badges")] = $badgers;

$temp[__("Registration date")] = format("{0} ({1} ago)", formatdate($user['regdate']), TimeUnits($daysKnown*86400));

$temp[__("Last online")] = format("{0} ({1} ago)", formatdate($user['lastactivity']), TimeUnits(time() - $user['lastactivity']));

$temp[__("Total posts")] = format("{0} ({1} per day)", $posts, $averagePosts);
$temp[__("Total threads")] = format("{0} ({1} per day)", $threads, $averageThreads);

$temp[__("Email")] = $emailField;
if($homepage)
	$temp[__("Website")] = $homepage;
if($user['location'])
	$temp[__("Location")] = htmlspecialchars($user['location']);
if($user['birthday'])
	$temp[__("Birthday")] = formatBirthday($user['birthday']);
	
if(HasPermission('admin.viewips'))
{
	$temp[__("Last user agent")] = htmlspecialchars($user['lastknownbrowser']);
	$temp[__("Last IP address")] = formatIP($user['lastip']);
}

$profileParts[__("Profile of ".$uname)] = $temp;

$bucket = "profileTable"; include(BOARD_ROOT."lib/pluginloader.php");

if ($user['bio'])
	$profileParts[__('About '.$uname)] = CleanUpPost($user['bio']);

$cpp = 10;
$total = FetchResult("SELECT
						count(*)
					FROM {usercomments}
					WHERE uid={0}", $id);

$from = (int)$_GET["from"];
if(!isset($_GET["from"]))
	$from = 0;
$realFrom = $total-$from-$cpp;
$realLen = $cpp;
if($realFrom < 0)
{
	$realLen += $realFrom;
	$realFrom = 0;
}
$rComments = Query("SELECT
		u.(_userfields),
		uc.id, uc.cid, uc.text, uc.date
		FROM {usercomments} uc
		LEFT JOIN {users} u ON u.id = uc.cid
		WHERE uc.uid={0}
		ORDER BY uc.date ASC LIMIT {1u},{2u}", $id, $realFrom, $realLen);

$pagelinks = PageLinksInverted(actionLink("profile", $id, "from=", $user['name']), $cpp, $from, $total);

$comments = array();
while($comment = Fetch($rComments))
{
	$cmt = array();
	
	$deleteLink = '';
	if($canDeleteComments || ($comment['cid'] == $loguserid && HasPermission('user.deleteownusercomments')))
		$deleteLink = "<small style=\"float: right; margin: 0px 4px;\">".
			actionLinkTag("&#x2718;", "profile", $id, "action=delete&cid=".$comment['id']."&token={$loguser['token']}")."</small>";
			
	$cmt['deleteLink'] = $deleteLink;
	
	$cmt['userlink'] = UserLink(getDataPrefix($comment, 'u_'));
	$cmt['formattedDate'] = relativedate($comment['date']);
	$cmt['text'] = CleanUpPost($comment['text']);
	
	$comments[] = $cmt;
}

$commentField = '';
if($canComment)
{
	$commentField = "
		<form name=\"commentform\" method=\"post\" action=\"".htmlentities(actionLink("profile"))."\">
			<input type=\"hidden\" name=\"id\" value=\"$id\">
			<input type=\"text\" name=\"text\" style=\"width: 80%;\" maxlength=\"255\">
			<input type=\"submit\" name=\"actionpost\" value=\"".__("Post")."\">
			<input type=\"hidden\" name=\"token\" value=\"{$loguser['token']}\">
		</form>";
}



RenderTemplate('profile', array(
	'username' => htmlspecialchars($uname), 
	'userlink' => UserLink($user),
	'profileParts' => $profileParts,
	'comments' => $comments,
	'commentField' => $commentField,
	'pagelinks' => $pagelinks));	

if (!$mobileLayout)
{
	$previewPost['text'] = Settings::get("profilePreviewText");

	$previewPost['num'] = 0;
	$previewPost['id'] = 0;

	foreach($user as $key => $value)
		$previewPost['u_'.$key] = $value;

	MakePost($previewPost, POST_SAMPLE);
}

$links = array();

if (HasPermission('admin.banusers') && $loguserid != $id)
{
	if ($user['primarygroup'] != Settings::get('bannedGroup'))
		$links[] = actionLinkTag('Ban user', 'banhammer', $id);
	else
		$links[] = actionLinkTag('Unban user', 'banhammer', $id, 'unban=1');
}

if(HasPermission('user.editprofile') && $loguserid == $id)
	$links[] = actionLinkTag(__("Edit my profile"), "editprofile");
else if(HasPermission('admin.editusers'))
	$links[] = actionLinkTag(__("Edit user"), "editprofile", $id);

if(HasPermission('admin.editusers'))
	$links[] = actionLinkTag(__('Edit permissions'), 'editperms', '', 'uid='.$id);

if(HasPermission('admin.viewpms'))
	$links[] = actionLinkTag(__("Show PMs"), "private", "", "user=".$id);

if(HasPermission('user.sendpms'))
	$links[] = actionLinkTag(__("Send PM"), "sendprivate", "", "uid=".$id);

$links[] = actionLinkTag(__("Show posts"), "listposts", $id, "", $user['name']);
$links[] = actionLinkTag(__("Show threads"), "listthreads", $id, "", $user['name']);

if ($loguserid) $links[] = $blockLayoutLink;

MakeCrumbs(array(actionLink("profile", $id, '', $user['name']) => htmlspecialchars($uname)), $links);

$title = format(__("{0}"), htmlspecialchars($uname));

function IsReallyEmpty($subject)
{
	$trimmed = trim(preg_replace("/&.*;/", "", $subject));
	return strlen($trimmed) == 0;
}


?>
