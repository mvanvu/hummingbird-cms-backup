<?php

return [
	'name'        => 'Backup',
	'group'       => 'System',
	'title'       => 'backup-plugin-title',
	'description' => 'backup-plugin-desc',
	'version'     => '1.0.0',
	'author'      => 'Mai vu',
	'authorEmail' => 'rainy@joomtech.net',
	'authorUrl'   => 'https://www.joomtech.net',
	'updateUrl'   => null,
	'params'      => [
		[
			'name'  => 'backup',
			'type'  => 'CmsBackup',
			'label' => 'backup-manage',
		],
	],
];
