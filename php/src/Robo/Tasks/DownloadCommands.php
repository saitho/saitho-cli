<?php
namespace Saitho\CLI\Robo\Tasks;

use Saitho\CLI\Robo\Service\DownloadDatabaseService;
use Saitho\CLI\Robo\Service\DownloadFileService;
use Saitho\CLI\Robo\Utility\ConfigTrait;
use Saitho\CLI\Robo\Utility\ServiceTrait;

trait DownloadCommands {
    use ConfigTrait;
    use ServiceTrait;

    /**
     * @return void
     * @throws \Exception
     */
    function downloadDb(string $databaseName = '', $argSavePath = null): void
    {
        $databasesConfig = $this->getExtraConfig()['databases'] ?? [];

        if (!count($databasesConfig)) {
            // @todo: deprecated, remove with breaking change
            $legacyConfig = $this->getExtraConfig()['download']['database'];
            echo 'DEPRECATION WARNING: database interactions should use the new "databases" key. Please update your configuration, as the old download.database syntax will stop working at some point.' . PHP_EOL;
            $config = $legacyConfig;
        } else {
            if (!$databaseName) {
                throw new \Exception('Missing argument "databaseName".');
            }
            $config = array_filter($databasesConfig, function ($cfg) use ($databaseName) {
                return $cfg['name'] === $databaseName;
            });
            if (count($config) > 1) {
                throw new \Exception('More than one database configuration with name "' . $databaseName . '" was found!');
            }
            if (!count($config)) {
                throw new \Exception('No database configuration with name "' . $databaseName . '" was found!');
            }
            $config = $config[0];

            if (!in_array('download', $config['allowed'] ?? [])) {
                throw new \Exception('"download" is not allowed on this database connection.');
            }
        }


        $connection = $config['connection'] ?? 'ssh-docker';

        /** @var DownloadDatabaseService $service */
        $service = $this->getServiceInstance(DownloadDatabaseService::class);
        switch ($connection) {
            case 'ssh-docker':
                $savePath = $config['save_path'] ?? $argSavePath ?? './db.sql';
                $warnings = $service->downloadFromDocker($config['connection_settings'], $savePath);
                if (count($warnings)) {
                    echo implode(PHP_EOL, $warnings);
                }
                echo 'Downloaded database dump to ' . $savePath . PHP_EOL;
                break;
            default:
                throw new \Exception('Invalid connection type "' . $connection . '" for this command.');
        }
    }

    /**
     * @param  array  $config
     * @return void
     * @throws \Exception
     */
    protected function downloadFile(DownloadFileService $service, array $config): void
    {
        $connection = $config['connection'] ?? 'ssh';

        switch ($connection) {
            case 'ssh':
                $remotePath = $config['path'] ?? '';
                $savePath = $config['save_path'] ?? '';
                $recursive = (bool)$config['recursive'] ?? false;
                $warnings = $service->download(
                    $config['connection_settings'],
                    $remotePath,
                    $savePath,
                    $recursive
                );
                if (count($warnings)) {
                    echo implode(PHP_EOL, $warnings);
                }
                echo 'Downloaded to ' . $savePath . PHP_EOL;
                break;
            default:
                throw new \Exception('Invalid connection type "' . $connection . '" for this command.');
        }
    }

    /**
     * @return void
     * @throws \Exception
     */
    function downloadFiles(): void
    {
        /** @var DownloadFileService $service */
        $service = $this->getServiceInstance(DownloadFileService::class);

        $config = $this->getExtraConfig()['download']['files'];
        if (is_array($config)) {
            foreach ($config as $c) {
                $this->downloadFile($service, $c);
            }
        } else {
            $this->downloadFile($service, $config);
        }
    }
}
