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
    function clearDb(): void
    {
        $config = $this->getExtraConfig()['clear']['database'];
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
