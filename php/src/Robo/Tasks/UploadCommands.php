<?php
namespace Saitho\CLI\Robo\Tasks;

use Saitho\CLI\Robo\Service\UploadDatabaseService;
use Saitho\CLI\Robo\Utility\ConfigTrait;
use Saitho\CLI\Robo\Utility\ServiceTrait;

trait UploadCommands {
    use ConfigTrait;
    use ServiceTrait;

    /**
     * @return void
     * @throws \Exception
     */
    function uploadDb(string $uploadPath, string $databaseName = ''): void
    {
        $databasesConfig = $this->getExtraConfig()['databases'] ?? [];

        if (!count($databasesConfig)) {
            // @todo: deprecated, remove with breaking change
            $legacyConfig = $this->getExtraConfig()['upload']['database'];
            echo 'DEPRECATION WARNING: database interactions should use the new "databases" key. Please update your configuration, as the old upload.database syntax will stop working at some point.' . PHP_EOL;
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

            if (!in_array('allowed', $config['allowed'] ?? [])) {
                throw new \Exception('"upload" is not allowed on this database connection.');
            }
        }

        $connection = $config['connection'] ?? 'ssh-docker';

        /** @var UploadDatabaseService $service */
        $service = $this->getServiceInstance(UploadDatabaseService::class);
        switch ($connection) {
            case 'ssh-docker':
                // Todo: backup warning, ask for confirmation
                $warnings = $service->uploadToDocker($config['connection_settings'], $uploadPath);
                if (count($warnings)) {
                    echo implode(PHP_EOL, $warnings);
                }
                echo 'Applied database dump from ' . $uploadPath . PHP_EOL;
                break;
            default:
                throw new \Exception('Invalid connection type "' . $connection . '" for this command.');
        }
    }
}
