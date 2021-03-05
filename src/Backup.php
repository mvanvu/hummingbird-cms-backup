<?php

namespace App\Plugin\Cms;

use App\Helper\AdminMenu;
use App\Helper\IconSvg;
use App\Helper\Text;
use App\Helper\Uri;
use App\Helper\User;
use App\Plugin\Plugin;

class Backup extends Plugin
{
	public function onRegisterAdminMenus(AdminMenu $adminMenu)
	{
		if (User::is('super'))
		{
			$adminMenu->addItem(
				'system',
				[
					'title' => IconSvg::render('database') . ' ' . Text::_('backup-system'),
					'url'   => Uri::route('backup/index'),
				]
			);
		}
	}
}