<?php

// WackoWiki ADMINISTRATION SUBSYSTEM

// ToDo:
// - rewrite backup/restore modules for more granulated backups,
//   and to span very big tables (> 2-5 Mb) across several
//   backup files
// - write modules for users administration (remove, ban and so on)
// - allow multiple admins login with personal credentials in
//   addition to recovery password login (in case of db corruption)

########################################################
##                  Wacko engine init                 ##
########################################################

// initialize engine api
require('classes/init.php');
$init = new init();

// define settings
$init->settings(); // populate from config.inc.php
$init->settings(); // initialize DBAL and populate from config table
$init->dbal();
$init->settings('theme_url',	$init->config['base_url'].'themes/'.$init->config['theme'].'/');
$init->settings('user_table',	$init->config['table_prefix'].'user');
$init->settings('cookie_path',	preg_replace('|https?://[^/]+|i', '', $init->config['base_url'].''));
$init->settings('cookie_hash',	hash('sha1', $init->config['base_url'].$init->config['system_seed']));

// misc
$init->session();

// start engine
$cache	= $init->cache();
$engine	= $init->engine();

// register locale resources
$init->engine('res');

// reconnect securely in ssl mode
if ($engine->config['ssl'] == true)
{
	if ( (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != "on" && empty($engine->config['ssl_proxy'])) || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != '443' ))
	{
		$engine->redirect(str_replace('http://', 'https://'.($engine->config['ssl_proxy'] ? $engine->config['ssl_proxy'].'/' : ''), $engine->config['base_url']).'admin.php');
	}
	else
	{
		$engine->config['base_url'] = str_replace('http://', 'https://'.($engine->config['ssl_proxy'] ? $engine->config['ssl_proxy'].'/' : ''), $engine->config['base_url']);
	}
}

// enable rewrite_mode to avoid href() appends '?page='
if ($engine->config['rewrite_mode'] == false)
{
	$engine->config['rewrite_mode'] = 1;
}

########################################################
##            End admin session and logout            ##
########################################################

if (isset($_GET['action']) && $_GET['action'] == 'logout')
{
	$engine->delete_cookie('admin', true, true);
	$engine->log(1, $engine->get_translation('LogAdminLogout', $engine->config['language']));
	$engine->redirect(( $engine->config['ssl'] == true ? str_replace('http://', 'https://'.($engine->config['ssl_proxy'] ? $engine->config['ssl_proxy'].'/' : ''), $engine->href()) : $engine->href() ));
	exit;
}

########################################################
##     Include admin modules and common functions     ##
########################################################

$dirs = array(
	'admin/common',
	'admin/modules'
);
foreach ($dirs as $dir)
{
	if ($dh = opendir($dir))
	{
		while (false !== ($filename = readdir($dh)))
		{
			if (is_dir($dir.'/'.$filename) !== true && substr($filename, -4) == '.php')
			{
				include($dir.'/'.$filename);
			}
		}
		closedir($dh);
	}
}

########################################################
##           Authorization & preparations             ##
########################################################

// recovery password
if ($engine->config['recovery_password'] == false)
{
	echo '<strong>'.$engine->get_translation('NoRecoceryPassword').'</strong><br />';
	echo $engine->get_translation('NoRecoceryPasswordTip');
	die();
}
else
{
	$pwd = hash('sha1', $engine->config['recovery_password']);
}

// recovery preauthorization
if (isset($_POST['password']))
{
	if (hash('sha1', $_POST['password']) == $pwd)
	{
		$engine->set_session_cookie('admin', hash('sha1', $_POST['password']), '', ( $engine->config['ssl'] == true ? 1 : 0 ));
		$_SESSION['CREATED'] = time();
		$_SESSION['LAST_ACTIVITY'] = time();
		$engine->log(1, $engine->get_translation('LogAdminLoginSuccess', $engine->config['language']));
		$engine->redirect(( $engine->config['ssl'] == true ? str_replace('http://', 'https://'.($engine->config['ssl_proxy'] ? $engine->config['ssl_proxy'].'/' : ''), $engine->href('admin.php')) : $engine->href('admin.php') ));
	}
	else
	{
		$engine->log(1, str_replace('%1', $_POST['password'], $engine->get_translation('LogAdminLoginFailed', $engine->config['language'])));
	}
}

// check authorization
$user = "";
if (isset($_COOKIE[$engine->config['cookie_prefix'].'admin'.'_'.$engine->config['cookie_hash']]) && $_COOKIE[$engine->config['cookie_prefix'].'admin'.'_'.$engine->config['cookie_hash']] == $pwd)
{
	$user = array('user_name' => $engine->config['admin_name']);
}

if ($user == false)
{
	header('Content-Type: text/html; charset='.$engine->get_charset());
?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<title>Authorization Admin</title>
	<link href="<?php echo rtrim($engine->config['base_url']); ?>admin/styles/backend.css" rel="stylesheet" type="text/css" media="screen" />
	</head>
	<body>
		<?php
		// here we show messages
		if ($message = $engine->get_message()) echo "<div class=\"info\">$message</div>";
		?>

		<strong><?php echo $engine->get_translation('Authorization'); ?></strong><br />
		<?php echo $engine->get_translation('AuthorizationTip'); ?>
		<br /><br />
		<form action="admin.php" method="post" name="emergency">
			<tt><strong><?php echo $engine->get_translation('LoginPassword'); ?>:</strong> <input name="password" type="password" autocomplete="off" value="" />
			<input id="submit" type="submit" value="ok" /></tt>
		</form>
	</body>
	</html>
<?php
	exit;
}
unset($pwd);

// setting temporary admin user context
global $_user;
$_user = $engine->get_user();
$engine->set_user($user, 0);

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 900)) //1800
{
	// last request was more than 15 minutes ago
	$engine->delete_cookie('admin', true, true);
	$engine->log(1, $engine->get_translation('LogAdminLogout', $engine->config['language']));

	//session_destroy();   // destroy session data in storage
	//session_unset();     // unset $_SESSION variable for the runtime
	$engine->set_message($engine->get_translation("LoggedOut"));
	$engine->redirect('admin.php');
}

$_SESSION['LAST_ACTIVITY'] = time(); // update last activity time stamp

if (!isset($_SESSION['CREATED']))
{
	$_SESSION['CREATED'] = time();
}
else if (time() - $_SESSION['CREATED'] > 1800)
{
	// session started more than 30 minates ago
	$engine->restart_user_session();
	//session_regenerate_id(true);    // change session ID for the current session an invalidate old session ID
	$_SESSION['CREATED'] = time();  // update creation time
}



########################################################
##                     Page header                    ##
########################################################

header('Content-Type: text/html; charset='.$engine->get_charset());
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>WackoWiki Management System</title>
<meta http-equiv="Content-Type" content="text/html; "/>
<link href="<?php echo rtrim($engine->config['base_url']); ?>admin/styles/atom.css" rel="stylesheet" type="text/css" media="screen" />
<link href="<?php echo rtrim($engine->config['base_url']); ?>admin/styles/wiki.css" rel="stylesheet" type="text/css" media="screen" />
<link href="<?php echo rtrim($engine->config['base_url']); ?>admin/styles/backend.css" rel="stylesheet" type="text/css" media="screen" />

</head>
<body>
<div id="header">
	<div id="pane">
		<div class="left"></div>
		<div class="middle">
			<a href="<?php echo rtrim($engine->config['base_url']); ?>admin.php"><img src="<?php echo rtrim($engine->config['base_url']); ?>files/wacko4.png" alt="WackoWiki" width="108" height="50"></img></a>
		</div>
		<div id="tools">
			<span style="font-family: 'Lucida Console', 'Courier New', monospace;">

				<?php $time_left = round((1800 - (time() - $_SESSION['CREATED'])) / 60);
				echo "Time left: ".$time_left." minutes"; ?>
				&nbsp;&nbsp;
				<?php echo $engine->compose_link_to_page('/', '', rtrim($engine->config['base_url'], '/')); ?>
				&nbsp;&nbsp;
				<?php echo ( $init->is_locked() === true ? '<strong>site closed</strong>' : 'site opened' ); ?>
				&nbsp;&nbsp;
				version <?php echo $engine->config['wacko_version']; ?>
			</span>
		</div>
		<br style="clear: right" />
		<div id="sections">
			<a href="<?php echo rtrim($engine->config['base_url']); ?>" title="open the home page, you do not quit administration">Home Page</a><a href="<?php echo rtrim($engine->config['base_url']); ?>admin.php?action=logout" title="quit system administration">Log out</a>
		</div>
	</div>
</div>

<?php

########################################################
##                     Main Menu                      ##
########################################################

?>
	<div id="menu" class="menu">
		<div class="sub">
			<ul>
			<li class="text submenu"><?php echo $module['lock']['cat']; ?>
			<?php echo ( isset($_REQUEST['mode']) && $_REQUEST['mode'] == 'lock' || (!$_GET && !$_POST)
				? "\n<ul>\n<li class=\"active\">"
				: "\n<ul>\n<li>" ); ?>
			<a href="admin.php">
			<?php echo $module['lock']['name']; ?></a>
			<?php echo "</li>\n";

			$category = $module['lock']['cat'];

			uasort($module,
				create_function(
					'$a, $b',
					'if ((array)$a["order"] < (array)$b["order"])
						return -1;
					else if ((array)$a["order"] > (array)$b["order"])
						return 1;
					else
						return 0;')
				);

			foreach ($module as $row)
			{
				if ($row['mode'] != 'lock')
				{
					echo ( $row['cat'] != $category
						? "</ul>\n</li>\n<li class=\"text submenu2\">".$row['cat']."<ul>\n"
						: "");
					echo ( isset($_REQUEST['mode']) && $_REQUEST['mode'] == $row['mode']
						? "<li class=\"active\">"
						: "<li>" ); ?>
					<a href="?mode=<?php echo $row['mode']; ?>" title="<?php echo $row['title']; ?>"><?php echo $row['name']; ?></a>
					<?php echo "</li>\n";
				}
				else
				{
					continue;
				}
				$category = $row['cat'];
			}
			unset($category);

?>
</ul></li></ul></div>
	</div>
<?php

########################################################
##                  Execute module                    ##
########################################################

?>

<div id="content">
<div id="page">
<?php
// here we show messages
if ($message = $engine->get_message()) echo "<div class=\"info\">$message</div>";
?>
<!-- begin page output -->

<?php

if (isset($_REQUEST['mode']) === true && ($_GET || $_POST))
{
	if (function_exists('admin_'.$_REQUEST['mode']) === true)
	{
		// page context
		$engine->tag = $engine->supertag = 'admin.php?mode='.$_REQUEST['mode'];
		$engine->context[++$engine->current_context] = $engine->tag;
		// module run
		$exec = 'admin_'.$_REQUEST['mode'];
		$exec($engine, $module[$_REQUEST['mode']]);
		$engine->current_context--;
	}
	else
	{
		echo '<em><br /><br />Error loading admin module "'.$_REQUEST['mode'].'.php": does not exists.</em>';
	}
}
else if (!($_GET && $_POST))
{
	$exec = 'admin_lock';
	$exec($engine, $module['lock']);
}

########################################################
##                     Page footer                    ##
########################################################

?>

<br />
<!-- end page output -->
</div>
</div>
<?php /*
<div id="tabs">
	<div class="controls"></div>
</div>
*/ ?>
<div id="footer">System <a href="http://wackowiki.org">WackoWiki</a></div>

<?php

// debugging info on script execution time and memory taken
$init->debug();

?>

</body>
</html>

<?php

########################################################
##             Finishing and cleaning out             ##
########################################################

// getting out of temp context
$engine->set_user($_user, 0);

?>