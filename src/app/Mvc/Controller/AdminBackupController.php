<?php

namespace App\Mvc\Controller;

use App\Helper\Assets;
use App\Helper\FileSystem;
use App\Helper\Text;
use App\Plugin\Cms\Backup as BackupHandler;
use App\Queue\Backup;
use App\Queue\Restore;
use App\Traits\Permission;

class AdminBackupController extends ControllerBase
{
	use Permission;

	public $role = 'super';

	public function backupAction()
	{
		BackupHandler::addQueue(Backup::class);
		sleep(1);

		return $this->response->setJsonContent(true);
	}

	public function restoreAction()
	{
		if ($this->request->isPost() && $backupFile = $this->request->getPost('backupFile'))
		{
			BackupHandler::addQueue(Restore::class, ['backupFile' => $backupFile]);
			sleep(1);

			return $this->response->setJsonContent(true);
		}

		return $this->response->setJsonContent(false);
	}

	public function indexAction()
	{
		Assets::addFromPlugin('js/backup.js', 'Cms', 'Backup');
		$backupDir   = PLUGIN_PATH . '/Cms/Backup/archived';
		$backupFiles = [];

		if (is_dir($backupDir) && $backups = FileSystem::scanFiles($backupDir))
		{
			foreach ($backups as $backup)
			{
				if (preg_match('/Backup-.*\.zip$/', $backup))
				{
					$backupFiles[] = basename($backup);
				}
				else
				{
					FileSystem::remove($backup);
				}
			}
		}

		$this->view->setVar('backupFiles', $backupFiles)
			->pick('Backup/Index');
	}

	public function removeAction()
	{
		$file = PLUGIN_PATH . '/Cms/Backup/archived/' . $this->request->getPost('backupFile');

		if ($this->request->isPost()
			&& is_file($file)
			&& FileSystem::remove($file)
		)
		{
			return $this->response->setJsonContent(
				[
					'message' => Text::_('backup-removed-successfully'),
					'status'  => 'success',
				]
			);
		}

		return $this->response->setJsonContent(
			[
				'message' => Text::_('backup-remove-failure'),
				'status'  => 'danger',
			]
		);
	}

	public function downloadAction()
	{
		$file = PLUGIN_PATH . '/Cms/Backup/archived/' . $this->request->get('file');

		FileSystem::stream($file);
	}
}