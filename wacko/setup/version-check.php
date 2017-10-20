<form action="<?php echo my_location() ?>?installAction=site-config" method="post">
<?php

write_config_hidden_nodes(['none' => '']);

?>
<p><?php echo $lang['Requirements']; ?></p>
	<?php
	/*
		Check PHP Version
	*/

	$php_version_result = version_compare(PHP_MIN_VERSION, PHP_VERSION, '<');
	?>
<h2><?php echo $lang['PHPVersion']; ?></h2>
<p class="notop"><?php echo $lang['PHPDetected']; ?> <?php echo phpversion() . '   ' . output_image($php_version_result); ?></p>
	<?php
	/*
		Check if mod_rewrite is installed
	*/
	?>
<h2><?php echo $lang['ModRewrite']; ?></h2>
<p class="notop"><?php echo $lang['ModRewriteInstalled']; ?>
	<?php
	if (function_exists('apache_get_modules'))
	{
		echo output_image(in_array('mod_rewrite', apache_get_modules()));
	}
	else
	{
		echo $lang['ModRewriteStatusUnknown'];
	} ?>
</p>
	<?php
	// TODO: check for mb_string, iconv, bcmath, pcre, pcre utf-8, ctype, gd, JSON, SPL extension
	// https://secure.php.net/manual/en/function.extension-loaded.php

	/*
	 Check which database extensions are installed and what versions of the db are there
	 */
	$database_result = extension_loaded('mysqli') || extension_loaded('pdo');

	/*
		With PDO it is not enough that we can just say "ok we've detected PDO".
		We have to actually confirm that one of the specific database types is enabled.
		Later when we support all the PDO types this can be removed but for now we
		only support a subset of them.

		This is a copy of the array from database-config.php
	*/

	$accepted_pdo_drivers	= [];
	$accepted_pdo_drivers[]	= 'mysql';

	$detected = 0;

	if (extension_loaded('pdo'))
	{
		// mssql mysql sqlite
		$drivers = PDO::getAvailableDrivers();

		for ($count = 0; $count < count($drivers); $count++)
		{
			// If you want to find the name out
			// print $drivers[$count];

			if (in_array($drivers[$count], $accepted_pdo_drivers))
			{
				$detected++;
				break;
			}
		}
	}
	?>
<h2><?php echo $lang['Database']; ?></h2>
<ul>
	<li>MySQLi   <?php echo output_image(extension_loaded('mysqli')); ?></li>
	<li>PDO   <?php echo output_image($detected > 0); ?></li>
</ul>
	<?php
	/*
	 Check file permissions
	 */

	// Try applying the correct permissions now and then display whether it worked or not, if they fail then the user will have to manually set the permissions
	@chmod (CACHE_CONFIG_DIR, 0777);
	@chmod (CACHE_FEED_DIR, 0777);
	@chmod (CACHE_PAGE_DIR, 0777);
	@chmod (CACHE_SQL_DIR, 0777);
	@chmod (CACHE_TEMPLATE_DIR, 0777);
	@chmod (CONFIG_FILE, 0777);
	@chmod (SITE_LOCK, 0660);
	@chmod (AP_LOCK, 0660);
	@chmod (UPLOAD_BACKUP_DIR, 0777);
	@chmod (UPLOAD_GLOBAL_DIR, 0777);
	@chmod (UPLOAD_PER_PAGE_DIR, 0777);
	@chmod (XML_DIR, 0777);
	@chmod (SITEMAP_XML, 0777);


	// If the cache directory is writable then we can enable caching as default
	echo '            <input type="hidden" name="config[cache]" value="' . (is_writable(CACHE_PAGE_DIR) ? '1' : $config['cache']) . '">' . "\n";

	$file_permissions_result =	   is_writable(CACHE_CONFIG_DIR)
								&& is_writable(CACHE_FEED_DIR)
								&& is_writable(CACHE_PAGE_DIR)
								&& is_writable(CACHE_SQL_DIR)
								&& is_writable(CACHE_TEMPLATE_DIR)
								&& is_writable(CONFIG_FILE)
								&& is_writable(SITE_LOCK)
								&& is_writable(AP_LOCK)
								&& is_writable(UPLOAD_BACKUP_DIR)
								&& is_writable(UPLOAD_GLOBAL_DIR)
								&& is_writable(UPLOAD_PER_PAGE_DIR)
								&& is_writable(XML_DIR)
								&& is_writable(SITEMAP_XML);
	?>
<h2><?php echo $lang['Permissions']; ?></h2>
<ul>
	<li><?php echo CACHE_CONFIG_DIR		. '   ' . output_image(is_writable(CACHE_CONFIG_DIR)); ?></li>
	<li><?php echo CACHE_FEED_DIR		. '   ' . output_image(is_writable(CACHE_FEED_DIR)); ?></li>
	<li><?php echo CACHE_PAGE_DIR		. '   ' . output_image(is_writable(CACHE_PAGE_DIR)); ?></li>
	<li><?php echo CACHE_SQL_DIR		. '   ' . output_image(is_writable(CACHE_SQL_DIR)); ?></li>
	<li><?php echo CACHE_TEMPLATE_DIR	. '   ' . output_image(is_writable(CACHE_TEMPLATE_DIR)); ?></li>
	<li><?php echo CONFIG_FILE			. '   ' . output_image(is_writable(CONFIG_FILE)); ?></li>
	<li><?php echo SITE_LOCK			. '   ' . output_image(is_writable(SITE_LOCK)); ?></li>
	<li><?php echo AP_LOCK				. '   ' . output_image(is_writable(AP_LOCK)); ?></li>
	<li><?php echo UPLOAD_BACKUP_DIR	. '   ' . output_image(is_writable(UPLOAD_BACKUP_DIR)); ?></li>
	<li><?php echo UPLOAD_GLOBAL_DIR	. '   ' . output_image(is_writable(UPLOAD_GLOBAL_DIR)); ?></li>
	<li><?php echo UPLOAD_PER_PAGE_DIR	. '   ' . output_image(is_writable(UPLOAD_PER_PAGE_DIR)); ?></li>
	<li><?php echo XML_DIR				. '   ' . output_image(is_writable(XML_DIR)); ?></li>
	<li><?php echo SITEMAP_XML			. '   ' . output_image(is_writable(SITEMAP_XML)); ?></li>
</ul>
	<?php
	/*
		End of checks, are we ready to install?
	*/
	?>
<h2><?php echo $lang['ReadyToInstall']; ?></h2>
	<?php
	if ($php_version_result && $database_result && $file_permissions_result)
	{
		?>
<p><?php echo $lang['Ready'];?></p>
<p><?php echo $lang['NotePermissions'];?></p>
<input type="submit" value="<?php echo $lang['Continue'];?>" class="next">
<?php
	}
	else if (!$php_version_result)
	{
?>
<p><?php echo $lang['ErrorMinPHPVersion']; ?></p>
<input type="button" value="<?php echo $lang['TryAgain'];?>" class="next" onClick="window.location.reload( true );">
<?php
	}
	else if (!$database_result)
	{
?>
<p><?php echo $lang['ErrorNoDbDriverDetected']; ?></p>
<input type="button" value="<?php echo $lang['TryAgain'];?>" class="next" onClick="window.location.reload( true );">
<?php
	}
	else if (!$file_permissions_result)
	{
?>
<p><?php echo $lang['NotePermissions']; ?></p>
<p><?php echo $lang['ErrorPermissions']; ?></p>
<input type="button" value="<?php echo $lang['TryAgain'];?>" class="next" onClick="window.location.reload( true );">
<input type="submit" value="<?php echo $lang['Continue'];?>" class="next">
<?php
	}
?>
</form>
