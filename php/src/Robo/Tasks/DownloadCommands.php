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
    function downloadDb(): void
    {
        $config = $this->getExtraConfig()['download']['database'];
        $connection = $config['connection'] ?? 'ssh-docker';

        /** @var DownloadDatabaseService $service */
        $service = $this->getServiceInstance(DownloadDatabaseService::class);
        switch ($connection) {
            case 'ssh-docker':
                $savePath = $config['save_path'] ?? './db.sql';
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
