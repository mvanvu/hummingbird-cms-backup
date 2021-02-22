<?php

namespace App\Queue;

use App\Helper\Console;
use App\Helper\Database;
use App\Helper\FileSystem;
use App\Helper\Service;
use Exception;
use ZipArchive;

class Restore extends QueueAbstract
{
	public function handle(): bool
	{
		set_time_limit(0);
		ini_set('memory_limit', -1);
		$console       = Console::getInstance();
		$file          = PLUGIN_PATH . '/Cms/Backup/archived/' . trim($this->data['backupFile'], './\\\\');
		$restoreTmpDir = TMP_PATH . '/backup-' . time();
		$zip           = new ZipArchive;
		$console->out('Start to extract the backup file...');

		if (!is_file($file)
			|| !@mkdir($restoreTmpDir, 0777, true)
			|| false === $zip->open($file)
			|| !$zip->extractTo($restoreTmpDir)
		)
		{
			FileSystem::remove($restoreTmpDir);
			$console->out('Can\'t restore system, the backup file not found.');
		}

		$zip->close();

		if (is_file($restoreTmpDir . '/install.sql'))
		{
			$console->out('Start restore SQL data...');
			$db         = Service::db();
			$dbPrefix   = Service::modelsManager()->getModelPrefix();
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
				$console->out('Restore database failed. Message: ' . $e->getMessage());

				return false;
			}

			$console->out('Restore SQL data successfully.');
		}

		$console->out('Start restore system files...');

		try
		{
			FileSystem::remove($restoreTmpDir . '/.idea');
			FileSystem::remove($restoreTmpDir . '/cache');
			FileSystem::copy($restoreTmpDir, BASE_PATH, true);
			$console->out('Restore system files successfully.');
		}
		catch (Exception $e)
		{
			FileSystem::remove($restoreTmpDir);
			$console->out('Restore files failed. Message: ' . $e->getMessage());

			return false;
		}

		$console->out('Removing temp files...');
		FileSystem::remove($restoreTmpDir);
		FileSystem::remove(CACHE_PATH);

		$console->out('Restore completed.');

		return true;
	}
}