<?php

namespace MaiVu\Hummingbird\Plugin\System\Backup;

use MaiVu\Hummingbird\Lib\Plugin;
use MaiVu\Hummingbird\Lib\Helper\Uri;
use MaiVu\Hummingbird\Lib\Helper\IconSvg;
use MaiVu\Hummingbird\Lib\Helper\Text;

class Backup extends Plugin
{
	public function onAfterSystemMenus()
	{
		$url  = Uri::route('backup/index');
		$icon = IconSvg::render('database');
		$text = Text::_('backup-system');

		return <<<HTML
 <li>
	<a href="{$url}">
		{$icon}
		{$text}
	</a>
</li>
HTML;

	}
}