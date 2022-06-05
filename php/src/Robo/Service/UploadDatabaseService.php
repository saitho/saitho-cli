<?php
namespace Saitho\CLI\Robo\Service;

use Robo\Task\Docker\Exec;
use Robo\Task\Remote\Rsync;
use Robo\Task\Remote\Ssh;
use Robo\TaskAccessor;

class UploadDatabaseService
{
    use TaskAccessor;

    /**
     * @return array array of warnings that occurred
     * @throws \Exception
     */
    public function uploadToDocker(array $connection_settings, string $uploadPath): array
    {
        $warnings = [];
        $sshUser = $connection_settings['user'] ?? '';
        $sshHost = $connection_settings['host'] ?? '';

        if (empty($sshHost)) {
            throw new \Exception('Missing ssh host configuration.');
        }

        $containerName = $connection_settings['container_name']?? '';
        $dbUser = $connection_settings['db_user'] ?? '';
        $dbPassword = $connection_settings['db_password']?? '';
        $dbDatabase = $connection_settings['db_name']?? '';

        if (empty($containerName)) {
            throw new \Exception('Missing container name.');
        } else if (empty($dbUser)) {
            throw new \Exception('Missing database user.');
        } else if (empty($dbPassword)) {
            throw new \Exception('Missing database password.');
        } else if (empty($dbDatabase)) {
            throw new \Exception('Missing database name.');
        }

        // Upload SQL file from local to remote
        $remotePath = '/tmp/newdump.sql';
        /** @var Rsync $rsyncTask */
        $rsyncTask = $this->task(Rsync::class);
        $result = $rsyncTask
            ->fromPath($uploadPath)
            ->toHost($sshHost)
            ->toUser($sshUser)
            ->toPath($remotePath)
            ->progress()
            ->run();
        if (!$result->wasSuccessful()) {
            throw new \Exception('Unable to upload database file to remote.');
        }

        // Apply DB dump in container
        /** @var Exec $execTask */
        $execTask = $this->task(Exec::class, $containerName);
        $importSql = $execTask
            ->interactive()
            ->exec('mysql -u' . $dbUser . ' -p' . $dbPassword . ' ' . $dbDatabase . ' < ' . $remotePath);
        $result = $this->task(Ssh::class, $sshUser . '@' . $sshHost)->exec($importSql)->run();
        if (!$result->wasSuccessful()) {
            throw new \Exception('Unable to execute SQL file in Docker container "' . $containerName . '": ' . $result->getOutputData());
        }

        // Remove temporary file
        //$removeTmpFile = $execTask
        //    ->exec('rm -f ' . $remotePath);
        //$result = $this->task(Ssh::class, $sshUser . '@' . $sshHost)->exec($removeTmpFile)->run();
        //if (!$result->wasSuccessful()) {
        //    $warnings[] = 'Note: The uploaded sql file on remote Docker container could not be removed.';
        //}
        $result = $this->task(Ssh::class, $sshUser . '@' . $sshHost)->exec('rm -f ' . $remotePath)->run();
        if (!$result->wasSuccessful()) {
            $warnings[] = 'Note: The uploaded sql file on remote server could not be removed.';
        }

        return $warnings;
    }
}
