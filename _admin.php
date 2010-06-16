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

$_menu['Blog']->addItem(__('Sitemaps'),'plugin.php?p=sitemaps','index.php?pf=sitemaps/icon.png',
		preg_match('/plugin.php\?p=sitemaps(&.*)?$/',$_SERVER['REQUEST_URI']),
		$core->auth->check('contentadmin',$core->blog->id));
?>