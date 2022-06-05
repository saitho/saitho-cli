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
    function uploadDb(string $uploadPath): void
    {
        $config = $this->getExtraConfig()['upload']['database'];
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
