<?php
namespace Saitho\CLI\Robo\Service;

use Robo\Task\Remote\Rsync;
use Robo\TaskAccessor;

class DownloadFileService
{
    use TaskAccessor;

    /**
     * @return array array of warnings that occurred
     * @throws \Exception
     */
    public function download(array $connection_settings, string $remotePath, string $savePath, bool $recursive = false): array
    {
        $sshUser = $connection_settings['user'] ?? '';
        $sshHost = $connection_settings['host'] ?? '';

        if (empty($sshHost)) {
            throw new \Exception('Missing ssh host configuration.');
        }

        // Copy folder from remote to local
        /** @var Rsync $rsyncTask */
        $rsyncTask = $this->task(Rsync::class);
        $result = $rsyncTask
            ->fromHost($sshHost)
            ->fromUser($sshUser)
            ->fromPath($remotePath)
            ->toPath($savePath);
        if ($recursive) {
            $result = $result->recursive();
        }
        $result = $result
            ->progress()
            ->run();
        if (!$result->wasSuccessful()) {
            throw new \Exception('Unable to copy file or directory from remote.');
        }
        return [];
    }
}
