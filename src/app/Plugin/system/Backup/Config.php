<?php

return [
	'name'        => 'Backup',
	'group'       => 'System',
	'title'       => 'backup-plugin-title',
	'description' => 'backup-plugin-desc',
	'version'     => '1.0.0',
	'author'      => 'Mai Vu',
	'authorEmail' => 'mvanvu@gmail.com',
	'authorUrl'   => 'https://github.com/mvanvu',
	'updateUrl'   => null,
	'params'      => [
		[
			'name'  => 'backup',
			'type'  => 'CmsBackup',
			'label' => 'backup-manage',
		],
	],
];
