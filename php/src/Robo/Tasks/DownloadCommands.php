<?php
namespace Saitho\CLI\Robo\Tasks;

use Robo\Task\Docker\Exec;
use Robo\Task\Remote\Rsync;
use Robo\Task\Remote\Ssh;
use Robo\TaskAccessor;
use Saitho\CLI\Robo\Utility\ConfigTrait;

trait DownloadCommands {
    use ConfigTrait;
    use TaskAccessor;

    function downloadDb() {
        $connection = $this->getExtraConfig()['download']['database']['connection'] ?? 'ssh-docker';
        switch ($connection) {
            case 'ssh-docker':
                $this->downloadDbDocker();
                break;
            default:
                throw new \Exception('Invalid connection type "' . $connection . '" for this command.');
        }
    }

    protected function downloadDbDocker() {
        $downloadConfig = $this->getExtraConfig()['download']['database'] ?? [];
        $connection_settings = $downloadConfig['connection_settings'];

        $sshUser = $connection_settings['user'] ?? '';
        $sshHost = $connection_settings['host'] ?? '';

        if (empty($sshHost)) {
            throw new \Exception('Missing ssh host configuration.');
        }

        $containerName = $connection_settings['container_name']?? '';
        $dbUser = $connection_settings['db_user'] ?? '';
        $dbPassword = $connection_settings['db_password']?? '';
        $dbDatabase = $connection_settings['db_name']?? '';
        $savePath = $downloadConfig['save_path'] ?? './db.sql';

        if (empty($containerName)) {
            throw new \Exception('Missing container name.');
        } else if (empty($dbUser)) {
            throw new \Exception('Missing database user.');
        } else if (empty($dbPassword)) {
            throw new \Exception('Missing database password.');
        } else if (empty($dbDatabase)) {
            throw new \Exception('Missing database name.');
        }

        /** @var Ssh $sshTask */
        $remoteBackupPath = '/tmp/backup.sql';

        // Make backup inside container
        /** @var Exec $execTask */
        $execTask = $this->task(Exec::class, $containerName);
        $makeBackup = $execTask
            ->exec('/usr/bin/mysqldump -u ' . $dbUser . ' --password=' . $dbPassword . ' ' . $dbDatabase . ' > ' . $remoteBackupPath);
        $sshTask = $this->task(Ssh::class, $sshUser . '@' . $sshHost);
        $result = $sshTask->exec($makeBackup)->run();
        if (!$result->wasSuccessful()) {
            throw new \Exception('Unable to create backup on Docker container "' . $containerName . '".');
        }

        // Copy backup file from remote to local
        /** @var Rsync $rsyncTask */
        $rsyncTask = $this->task(Rsync::class);
        $result = $rsyncTask
            ->fromHost($sshHost)
            ->fromUser($sshUser)
            ->fromPath($remoteBackupPath)
            ->toPath($savePath)
            ->progress()
            ->run();
        if (!$result->wasSuccessful()) {
            throw new \Exception('Unable to copy database file from remote.');
        }

        // Remove temporary backup file
        $execTask = $this->task(Exec::class, $containerName);
        $removeTmpFile = $execTask
            ->exec('rm -f ' . $remoteBackupPath);
        $sshTask = $this->task(Ssh::class, $sshUser . '@' . $sshHost);
        $result = $sshTask->exec($removeTmpFile)->run();
        if (!$result->wasSuccessful()) {
            echo 'Note: The temporary database backup file on remote server could not be removed.\n';
        }

        echo 'Downloaded database dump to ' . $savePath . PHP_EOL;
    }
}
