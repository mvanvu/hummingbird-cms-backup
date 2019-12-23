<?php

namespace MaiVu\Hummingbird\Lib\Mvc\Controller;

use Phalcon\Db\Enum;
use Phalcon\Db\Adapter\Pdo\Mysql;
use MaiVu\Hummingbird\Lib\Helper\Asset;
use MaiVu\Hummingbird\Lib\Helper\Date;
use MaiVu\Hummingbird\Lib\Helper\FileSystem;
use MaiVu\Hummingbird\Lib\Helper\Text;
use MaiVu\Hummingbird\Lib\Helper\User;
use MaiVu\Hummingbird\Lib\Helper\Database;
use MaiVu\Hummingbird\Lib\Factory;
use ZipArchive, Exception;

class AdminBackupController extends ControllerBase
{
	protected function responsePartial($message, $status, $end = false)
	{
		echo '###' . str_pad(json_encode(['message' => $message, 'status' => $status]), 4096, ' ');
		ob_flush();
		flush();

		if ($end)
		{
			ob_end_flush();
			exit(0);
		}

		usleep(25000);
	}

	protected function startHandle()
	{
		if (!User::getInstance()->access('super'))
		{
			$this->dispatcher->forward(
				[
					'controller' => 'admin_error',
					'action'     => 'show',
					'params'     => [
						'code'    => 403,
						'title'   => Text::_('403-title'),
						'message' => Text::_('403-message'),
					],
				]
			);

			return false;
		}

		$this->response
			->setHeader('Content-Type', 'text/html; charset=UTF-8')
			->sendHeaders();

		if (function_exists('set_time_limit'))
		{
			set_time_limit(0);
		}

		return true;
	}

	public function backupAction()
	{
		if (!$this->startHandle())
		{
			return false;
		}

		/** @var Mysql $db */
		$db     = $this->getDI()->getShared('db');
		$prefix = $this->modelsManager->getModelPrefix();
		$data   = [
			'SET FOREIGN_KEY_CHECKS = 0;',
			'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";',
			'SET time_zone = "+00:00";'
		];
		$this->responsePartial(Text::_('backup-step-1'), 'warning');

		foreach ($db->listTables() as $table)
		{
			if ($result = $db->fetchOne('SHOW CREATE TABLE ' . $table, Enum::FETCH_NUM))
			{
				$tmpTable = str_replace($prefix, '#__', $result[0]);
				$data[]   = '--' . PHP_EOL . '-- Table structure for table `' . $tmpTable . '`' . PHP_EOL . '--' . PHP_EOL;
				$data[]   = 'DROP TABLE IF EXISTS `' . $tmpTable . '`;';
				$data[]   = str_replace($prefix, '#__', $result[1]) . ';';

				if ($items = $db->fetchAll('SELECT * FROM ' . $table))
				{
					$table     = $tmpTable;
					$insertSql = 'INSERT INTO `' . $table . '` (';

					foreach (array_keys($items[0]) as $column)
					{
						$insertSql .= '`' . $column . '`,';
					}

					$insertSql = rtrim($insertSql, ',') . ') VALUES ';

					foreach ($items as $item)
					{
						$values = '(';

						foreach ($item as $value)
						{
							$values .= $db->escapeString((string) $value) . ',';
						}

						$insertSql .= rtrim($values, ',') . '),';
					}

					$data[] = PHP_EOL . '--' . PHP_EOL . '-- Dumping data for table `' . $table . '`' . PHP_EOL . '--' . PHP_EOL;
					$data[] = rtrim($insertSql, ',') . ';' . PHP_EOL;
				}
			}
		}

		$data[] = 'SET FOREIGN_KEY_CHECKS = 1;';
		$this->responsePartial(Text::_('backup-step-2'), 'success');
		$dir = PLUGIN_PATH . '/System/Backup/Archived';

		if (!is_dir($dir))
		{
			mkdir($dir, 0755);
		}

		$zipFile = $dir . '/Backup-' . Date::getInstance()->toDisplay('Y-m-d_H:i:s') . '.zip';
		$zip     = new ZipArchive;
		$res     = $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

		if (false === $res)
		{
			$this->responsePartial(Text::_('backup-zip-error', ['error' => $res]), 'danger', true);
		}

		$this->responsePartial(Text::_('backup-step-3'), 'warning');
		$files = FileSystem::scanFiles(
			BASE_PATH,
			true,
			[
				'.DS_Store',
				'.idea',
				'.git',
				'.svn',
				'CVS',
				'__MACOSX',
				'thumbs',
				'/app/Plugin/System/backup/Archived',
				'/config.ini',
				'/cache',
				'/vendor',
				'/public/assets/compressed',
			]
		);

		$this->responsePartial(Text::_('backup-step-4'), 'success');
		$this->responsePartial(Text::_('backup-step-5'), 'warning');

		foreach ($files as $file)
		{
			$localName = preg_replace('#^' . preg_quote(BASE_PATH, '#') . '/#', '', $file);
			$zip->addFile($file, $localName);
		}

		$zip->addFromString('install.sql', implode(PHP_EOL, $data));
		$zip->deleteName('app/Plugin/System/Backup/Archived/');
		$zip->close();
		$this->responsePartial(Text::_('backup-completed'), 'success');
		$this->responsePartial($this->view->getPartial('Backup/ListItem', ['backupFile' => basename($zipFile)]), 'success', true);
	}

	public function restoreAction()
	{
		if (!$this->startHandle())
		{
			return false;
		}

		$file          = PLUGIN_PATH . '/System/Backup/Archived/' . $this->request->getPost('backupFile');
		$restoreTmpDir = PLUGIN_PATH . '/System/Backup/tmp' . time();
		$zip           = new ZipArchive;
		$this->responsePartial(Text::_('backup-restore-step-1'), 'warning');

		if (!$this->request->isPost()
			|| !is_file($file)
			|| !@mkdir($restoreTmpDir, 0777)
			|| true !== $zip->open($file)
			|| !$zip->extractTo($restoreTmpDir)
		)
		{
			$this->responsePartial(Text::_('backup-restore-error'), 'danger', true);
		}

		$zip->close();

		if (is_file($restoreTmpDir . '/install.sql'))
		{
			$this->responsePartial(Text::_('backup-restore-step-2'), 'warning');

			/** @var Mysql $db */
			$db         = Factory::getService('db');
			$dbPrefix   = $this->modelsManager->getModelPrefix();
			$sqlContent = str_replace('#__', $dbPrefix, file_get_contents($restoreTmpDir . '/install.sql'));

			try
			{
				foreach (Database::splitSql($sqlContent) as $query)
				{
					$db->execute($query);
				}
			}
			catch (Exception $e)
			{
				FileSystem::remove($restoreTmpDir);
				$this->responsePartial($e->getMessage(), 'danger', true);
			}

			$this->responsePartial(Text::_('backup-restore-step-3'), 'success');
		}

		$this->responsePartial(Text::_('backup-restore-step-4'), 'warning');

		try
		{
			FileSystem::copy($restoreTmpDir, BASE_PATH);
			$this->responsePartial(Text::_('backup-restore-step-5'), 'success');
		}
		catch (Exception $e)
		{
			FileSystem::remove($restoreTmpDir);
			$this->responsePartial($e->getMessage(), 'danger', true);
		}

		$this->responsePartial(Text::_('backup-restore-step-6'), 'warning');
		$removeDirs = [
			$restoreTmpDir,
			BASE_PATH . '/cache',
			PUBLIC_PATH . '/assets/compressed',
		];

		foreach ($removeDirs as $removeDir)
		{
			if (is_dir($removeDir))
			{
				FileSystem::remove($removeDir);
			}
		}

		$this->responsePartial(Text::_('backup-restore-completed'), 'success', true);
	}

	public function indexAction()
	{
		$this->adminBase();
		Asset::addFile(PLUGIN_PATH . '/System/Backup/Asset/Js/backup.js');
		$backupDir   = PLUGIN_PATH . '/System/Backup/Archived';
		$backupFiles = [];

		if (is_dir($backupDir)
			&& ($backups = FileSystem::scanFiles($backupDir))
		)
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

		$this->view
			->setVar('backupFiles', $backupFiles)
			->pick('Backup/Index');
	}

	public function removeAction()
	{
		$file = PLUGIN_PATH . '/System/Backup/Archived/' . $this->request->getPost('backupFile');

		if ($this->request->isPost()
			&& User::getInstance()->access('super')
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
}