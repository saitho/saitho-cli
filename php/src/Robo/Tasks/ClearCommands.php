<?php
namespace Saitho\CLI\Robo\Tasks;

use Saitho\CLI\Robo\Service\ClearDatabaseService;
use Saitho\CLI\Robo\Utility\ConfigTrait;
use Saitho\CLI\Robo\Utility\ServiceTrait;

trait ClearCommands {
    use ConfigTrait;
    use ServiceTrait;

    /**
     * @return void
     * @throws \Exception
     */
    function clearDb(string $databaseName = ''): void
    {
        $databasesConfig = $this->getExtraConfig()['databases'] ?? [];

        if (!count($databasesConfig)) {
            // @todo: deprecated, remove with breaking change
            $legacyConfig = $this->getExtraConfig()['clear']['database'];
            echo 'DEPRECATION WARNING: database interactions should use the new "databases" key. Please update your configuration, as the old clear.database syntax will stop working at some point.' . PHP_EOL;
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

            if (!in_array('clear', $config['allowed'] ?? [])) {
                throw new \Exception('"clear" is not allowed on this database connection.');
            }
        }
        $connection = $config['connection'] ?? 'ssh-docker';

        /** @var ClearDatabaseService $service */
        $service = $this->getServiceInstance(ClearDatabaseService::class);
        switch ($connection) {
            case 'ssh-docker':
                // Todo: backup warning, ask for confirmation
                $warnings = $service->clearDocker($config['connection_settings']);
                if (count($warnings)) {
                    echo implode(PHP_EOL, $warnings);
                }
                echo 'Cleared database '. PHP_EOL;
                break;
            default:
                throw new \Exception('Invalid connection type "' . $connection . '" for this command.');
        }
    }
}
