<?php

namespace App\Plugin\Cms;

use App\Helper\IconSvg;
use App\Helper\Text;
use App\Helper\Uri;
use App\Helper\User;
use App\Plugin\Plugin;

class Backup extends Plugin
{
	public function onRegisterAdminMenus(&$menus)
	{
		if (User::is('super'))
		{
			$menus['system']['items'][] = [
				'title' => IconSvg::render('database') . ' ' . Text::_('backup-system'),
				'url'   => Uri::route('backup/index'),
			];
		}
	}
}