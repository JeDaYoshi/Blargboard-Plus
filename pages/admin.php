<?php
//  Blargboard -- Admin Dashboard

if (!defined('BLARG')) die();


CheckPermission('admin.viewadminpanel');

$title = __("Dashboard");

MakeCrumbs(array(actionLink("admin") => __('Admin')));


if (function_exists('curl_init'))
	$protstatus = __('Enabled (using cURL)');
else if (ini_get('allow_url_fopen'))
	$protstatus = __('Enabled (using fopen)');
else
	$protstatus = __('Disabled');


$adminInfo = array();
$adminInfo[__('Proxy protection')] = $protstatus;
$adminInfo[__('Last viewcount milestone')] = $misc['milestone'];

$adminConfig = array();

if (HasPermission('admin.editforums'))		$adminConfig[] = actionLinkTag(__("<button>Manage Forums</button>"), "editfora");
if (HasPermission('admin.editsettings'))
{
	$adminConfig[] = actionLinkTag(__("<button>General Settings</button>"), "editsettings");
	$adminConfig[] = actionLinkTag(__("<button>Edit Homepage</button>"), "editsettings", '', 'field=homepageText');
	$adminConfig[] = actionLinkTag(__("<button>Edit FAQ</button>"), "editsettings", '', 'field=faqText');
	$adminConfig[] = actionLinkTag(__("<button>Plugin Manager</button>"), "pluginmanager");
	$adminConfig[] = actionLinkTag(__("<button>Assign Badges</button>"), "userbadges");
}

$adminTools = array();

if (HasPermission('admin.manageipbans'))	$adminTools[] = actionLinkTag(__("<button>IP Bans</button>"), "ipbans");
if ($loguser['root']) 						$adminTools[] = actionLinkTag(__("<button>Calculate Stats</button>"), "recalc");
if ($loguser['root'])						$adminTools[] = actionLinkTag(__("<button>Optimize Tables</button>"), "optimize");
if (HasPermission('admin.viewlog'))			$adminTools[] = actionLinkTag(__("<button>Board Log</button>"), "log");
if (HasPermission('admin.ipsearch'))		$adminTools[] = actionLinkTag(__("<button>Rereg Radar</button>"), "reregs");

$bucket = "adminpanel"; include(BOARD_ROOT."lib/pluginloader.php");

RenderTemplate('adminpanel', array('adminTools' => $adminTools, 'adminConfig' => $adminConfig));

?>
