<?php

namespace App\Queue;

use App\Helper\Console;
use App\Helper\Date;
use App\Helper\FileSystem;
use App\Helper\Service;
use Phalcon\Db\Enum;
use ZipArchive;

class Backup extends QueueAbstract
{
	public function handle(): bool
	{
		set_time_limit(0);
		ini_set('memory_limit', -1);
		$console = Console::getInstance();
		$db      = Service::db();
		$prefix  = Service::modelsManager()->getModelPrefix();
		$data    = [
			'SET FOREIGN_KEY_CHECKS = 0;',
			'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";',
			'SET time_zone = "+00:00";'
		];

		$console->out('Start backup SQL data...');

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
		$console->out('Backup SQL data completed.');
		$dir = PLUGIN_PATH . '/Cms/Backup/archived';

		if (!is_dir($dir))
		{
			mkdir($dir, 0755);
		}

		$zipFile = $dir . '/Backup-' . Date::now('UTC')->toDisplay('Y-m-d_H:i:s') . '.zip';
		$zip     = new ZipArchive;

		if (false === $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE))
		{
			$console->out('Can\'t open zip archive.');

			return false;
		}

		$console->out('Start collect system files...');
		FileSystem::scanFiles(
			BASE_PATH,
			true,
			function ($file) use ($zip) {
				if ($file !== BASE_PATH . '/config.php'
					&& 0 !== strpos($file, BASE_PATH . '/app/Plugin/Cms/Backup/archived')
					&& 0 !== strpos($file, BASE_PATH . '/cache')
					&& 0 !== strpos($file, BASE_PATH . '/tmp')
				)
				{
					$zip->addFile($file, preg_replace('#^' . preg_quote(BASE_PATH, '#') . '/#', '', $file));
				}
			}
		);

		$console->out('Collect system files completed.');
		$console->out('Start compress system files...');
		$zip->addFromString('install.sql', implode(PHP_EOL, $data));
		$zip->close();
		$console->out('Backup completed.');

		return true;
	}
}