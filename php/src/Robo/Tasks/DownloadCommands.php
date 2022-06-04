<?php
namespace Saitho\CLI\Robo\Tasks;

use Saitho\CLI\Robo\Service\DownloadDatabaseService;
use Saitho\CLI\Robo\Utility\ConfigTrait;
use Saitho\CLI\Robo\Utility\ServiceTrait;

trait DownloadCommands {
    use ConfigTrait;
    use ServiceTrait;

    function downloadDb(): void
    {
        $connection = $this->getExtraConfig()['download']['database']['connection'] ?? 'ssh-docker';
        switch ($connection) {
            case 'ssh-docker':
                $config = $this->getExtraConfig()['download']['database'];
                $service = $this->getServiceInstance(DownloadDatabaseService::class);
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


}
