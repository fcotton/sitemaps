<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of Sitemaps, a plugin for DotClear2.
# Copyright (c) 2006-2009 Pep and contributors.
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------
if (!defined('DC_CONTEXT_ADMIN')) { exit; }

$periods = array(
		__('undefined') => 0,
		__('always')    => 1,
		__('hourly')    => 2,
		__('daily')     => 3,
		__('weekly')    => 4,
		__('monthly')   => 5,
		__('never')     => 6
	);

$map_parts = new ArrayObject(array(
		__('Homepage')		=> 'home',
		__('Feeds')		=> 'feeds',
		__('Posts')		=> 'posts',
		__('Pages')		=> 'pages',
		__('Categories')	=> 'cats',
		__('Tags')		=> 'tags'
	));

# --BEHAVIOR-- sitemapsDefineParts
$core->callBehavior('sitemapsDefineParts',$map_parts);

$msg = '';
$default_tab = 'sitemaps_options';
$active = $core->blog->settings->sitemaps_active;

foreach ($map_parts as $k => $v) {
	${$v.'_url'} = $core->blog->settings->get('sitemaps_'.$v.'_url');
	${$v.'_pr'}  = $core->blog->settings->get('sitemaps_'.$v.'_pr');
	${$v.'_fq'}  = $core->blog->settings->get('sitemaps_'.$v.'_fq');
}

$engines = @unserialize($core->blog->settings->sitemaps_engines);
$default_pings = explode(',',$core->blog->settings->sitemaps_pings);

// Save new configuration
if (!empty($_POST['saveconfig'])) {
	try
	{
		$core->blog->settings->setNameSpace('sitemaps');

		$active = (empty($_POST['active']))?false:true;
		$core->blog->settings->put('sitemaps_active',$active,'boolean');

		foreach ($map_parts as $k => $v) {
			${$v.'_url'} = (empty($_POST[$v.'_url']))?false:true;
			${$v.'_pr'}  = min(abs((float)$_POST[$v.'_pr']),1);
			${$v.'_fq'}  = min(abs(intval($_POST[$v.'_fq'])),6);

			$core->blog->settings->put('sitemaps_'.$v.'_url', ${$v.'_url'}, 'boolean');
			$core->blog->settings->put('sitemaps_'.$v.'_pr' , ${$v.'_pr'}, 'double');
			$core->blog->settings->put('sitemaps_'.$v.'_fq' , ${$v.'_fq'}, 'integer');
		}
		$core->blog->triggerBlog();
		http::redirect('plugin.php?p='.$p.'&conf=1');
		exit;
	}
	catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}

// Save ping preferences
elseif (!empty($_POST['saveprefs']))
{
	try {
		$new_prefs = '';
		if (!empty($_POST['pings'])) {
			$new_prefs = implode(',',$_POST['pings']);
		}
		$core->blog->settings->setNamespace('sitemaps');
		$core->blog->settings->put('sitemaps_pings',$new_prefs,'string');
		http::redirect('plugin.php?p='.$p.'&prefs=1');
		exit;
	}
	catch (Exception $e) {
		$default_tab = 'sitemaps_notifications';
		$core->error->add($e->getMessage());
	}
}

// Send ping(s)
elseif (!empty($_POST['ping']))
{
	$pings = empty($_POST['pings']) ? $default_pings : $_POST['pings'];
	$sitemap_url = $core->blog->url.$core->url->getBase('gsitemap');
	foreach ($pings as $service) {
		try {
			if (!array_key_exists($service,$engines)) continue;
			if (false === netHttp::quickGet($engines[$service]['url'].'?sitemap='.urlencode($sitemap_url))) {
				throw new Exception(__('Response does not seem OK'));
			}
			$results[] = sprintf('%s : %s', __('success'), $engines[$service]['name']);
		}
		catch (Exception $e) {
			$results[] = sprintf('%s : %s : %s', __('Failure'), $engines[$service]['name'],$e->getMessage());
		}
	}
	$msg = __('Ping(s) sent');
	$msg .= '<br />'.implode("<br />\n",$results);
}

else {
	if (isset($_GET['prefs'])) {
		$msg = __('New preferences saved');
		$default_tab = 'sitemaps_notifications';
	}
	elseif (isset($_GET['conf'])) {
		$msg = __('Configuration successfully updated.');
		$default_tab = 'sitemaps_options';
	}
}
?>
<html>
<head>
	<title><?php echo __('XML Sitemaps'); ?></title>
	<?php echo dcPage::jsPageTabs($default_tab); ?>
</head>
<body>
<h2><?php echo html::escapeHTML($core->blog->name); ?> &gt; <?php echo __('XML Sitemaps'); ?></h2>
<?php if (!empty($msg)) echo '<p class="message">'.$msg.'</p>'; ?>
<!-- Configuration panel -->
<div class="multi-part" id="sitemaps_options" title="<?php echo __('Configuration'); ?>">
	<form method="post" action="<?php echo http::getSelfURI(); ?>">
	<fieldset>
		<legend><?php echo __('Plugin activation'); ?></legend>
		<p class="field">
		<label class=" classic"><?php echo form::checkbox('active', 1, $active); ?>&nbsp;
		<?php echo __('Enable sitemaps');?>
		</label>
		</p>
	</fieldset>
	<fieldset>
		<legend><?php echo __('For your information'); ?></legend>
		<p>
		<?php echo __("This blog's Sitemap URL:"); ?>&nbsp;
		<strong><?php echo $core->blog->url.$core->url->getBase('gsitemap'); ?></strong>
		</p>
	</fieldset>
	<fieldset>
		<legend><?php echo __('Elements to integrate'); ?></legend>
		<table class="maximal">
		<tbody>
<?php foreach ($map_parts as $k => $v) : ?>
		<tr>
		<td>
			<label class=" classic">
			<?php echo form::checkbox($v.'_url', 1, ${$v.'_url'});?>
			&nbsp;<?php echo $k;?>
			</label>
		</td>
		<td>
			<label class=" classic"><?php echo __('Priority'); ?>&nbsp;
			<?php echo form::field($v.'_pr', 4, 4, ${$v.'_pr'}); ?>
			</label>
		</td>
		<td>
			<label class=" classic"><?php echo __('Periodicity'); ?>&nbsp;
			<?php echo form::combo($v.'_fq', $periods, ${$v.'_fq'}); ?>
			</label>
		</td>
	</tr>
<?php endforeach; ?>
	</tbody>
	</table>
	</fieldset>

	<p><input type="hidden" name="p" value="sitemaps" />
	<?php echo $core->formNonce(); ?>
	<input type="submit" name="saveconfig" value="<?php echo __('Save configuration'); ?>" />
<?php if ($active) : ?>
	&nbsp;<input class="submit" type="submit" name="ping" value="<?php echo __('Ping search engines'); ?>" />
<?php endif; ?>
	</p>
	</form>
</div>

<!-- Notifications panel -->
<div class="multi-part" id="sitemaps_notifications" title="<?php echo __('Search engines notification'); ?>">
	<form method="post" action="<?php echo http::getSelfURI(); ?>">
	<fieldset>
		<legend><?php echo __('Available search engines'); ?></legend>
		<table class="maximal">
			<tbody>
<?php foreach ($engines as $eng => $eng_infos) : ?>
			<tr>
			<td>
				<label class=" classic">
				<?php echo form::checkbox('pings[]', $eng, in_array($eng,$default_pings));?>
				&nbsp;<?php echo $eng_infos['name'];?>
				</label>
			</td>
			</tr>
<?php endforeach; ?>
			</tbody>
		</table>
	</fieldset>
	<p><input type="hidden" name="p" value="sitemaps" />
	<?php echo $core->formNonce(); ?>
	<input type="submit" name="saveprefs" value="<?php echo __('Save preferences'); ?>" />
<?php if ($active) : ?>
	&nbsp;<input class="submit" type="submit" name="ping" value="<?php echo __('Ping search engines'); ?>" />
<?php endif; ?>
	</p>
	</form>
</div>

</body>
</html>
