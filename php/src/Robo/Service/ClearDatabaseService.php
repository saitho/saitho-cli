<?php
namespace Saitho\CLI\Robo\Service;

use Robo\Task\Docker\Exec;
use Robo\Task\Remote\Ssh;
use Robo\TaskAccessor;

class ClearDatabaseService
{
    use TaskAccessor;

    /**
     * @return array array of warnings that occurred
     * @throws \Exception
     */
    public function clearDocker(array $connection_settings): array
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

        // Fetch all tables
        /** @var Exec $execTask */
        $execTask = $this->task(Exec::class, $containerName);
        $importSql = $execTask
            ->interactive()
            ->exec('mysql -u' . $dbUser . ' -p' . $dbPassword . ' ' . $dbDatabase . ' -e "show tables"');

        /** @var Ssh $sshTask */
        $result = $this->task(Ssh::class, $sshUser . '@' . $sshHost)->silent(true)->exec($importSql)->run();
        if (!$result->wasSuccessful()) {
            throw new \Exception('Unable to fetch tables from database "' . $dbDatabase . '" in Docker container "' . $containerName . '": ' . $result->getOutputData());
        }
        $tables = array_map(function ($name) {
            return '\`' . trim($name) . '\`';
        }, explode(PHP_EOL, $result->getOutputData()));
        array_shift($tables); // remove first entry which is table header
        if (!count($tables)) {
            $warnings[] = 'Database was already empty.';
            return $warnings;
        }

        // Drop tables
        $dropTables = $execTask
            ->interactive()
            ->exec('mysql -u' . $dbUser . ' -p' . $dbPassword . ' ' . $dbDatabase . ' -e "SET foreign_key_checks = 0;DROP TABLE IF EXISTS ' . implode(',', $tables) . ';SET foreign_key_checks = 1;"');
        $result = $this->task(Ssh::class, $sshUser . '@' . $sshHost)->exec($dropTables)->run();
        if (!$result->wasSuccessful()) {
            throw new \Exception('Unable to drop tables from database "' . $dbDatabase . '" in Docker container "' . $containerName . '": ' . $result->getOutputData());
        }

        return $warnings;
    }
}
