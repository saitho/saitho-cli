<?php
namespace Saitho\CLI\Robo\Tasks;

use Saitho\CLI\Robo\Utility\Config;
use Robo\Collection\CollectionBuilder;
use Saitho\CLI\Robo\Utility\ConfigTrait;

trait BuildCommands {
    use ConfigTrait;

    protected function _buildCss(CollectionBuilder &$builder)
    {
        $extensions = $this->getExtraConfig()['typo3']['extensions'] ?? [];
        $typo3WebDir = $this->getExtraConfig('typo3/cms')['web-dir'] ?? 'public';

        /** @var \RoboFile|CollectionBuilder $builder */
        foreach ($extensions as $extKey => $settings) {
            $buildSettings = $settings['build'] ?? [];
            if (empty($buildSettings)) {
                continue;
            }
            $binary = $buildSettings['builder'] ?? 'npm';
            $script = $buildSettings['script'] ?? 'build';

            if (!in_array($binary, ['npm', 'pnpm', 'yarn'])) {
                throw new \Exception(
                    sprintf('Unknown builder "%s" for extension "%s".', $binary, $extKey)
                );
            }
            $extPath = $typo3WebDir . '/typo3conf/ext/' . $extKey;
            $builder->taskNpmInstall($binary)
                ->dir($extPath);
            $builder->taskExec($binary . ' run ' . $script)
                ->dir($extPath);
        }
    }

    function _buildAssets(CollectionBuilder &$builder)
    {
        $this->_buildCss($builder);
    }

    function buildAssets()
    {
        $builder = $this->getBuilder();
        $this->_buildAssets($builder);
        return $builder;
    }

    function buildCss()
    {
        $builder = $this->getBuilder();
        $this->_buildCss($builder);
        return $builder;
    }

    function buildDocker($opts = ['push' => false, 'ver' => '', 'autover' => false])
    {
        $dockerUser = getenv('DOCKER_LOGIN_USER') ?? '';
        $dockerPassword = getenv('DOCKER_LOGIN_PASSWORD') ?? '';
        $dockerRegistry = getenv('DOCKER_LOGIN_REGISTRY') ?? '';
        // If environment is set, login into Docker
        if (!empty($dockerUser) && !empty($dockerPassword)) {
            $dockerRegistry = !empty($dockerRegistry) ? ' ' . $dockerRegistry : '';
            exec('echo "' . $dockerPassword . '" | docker login --username ' . $dockerUser . ' --password-stdin' . $dockerRegistry);
        }

        $imageName = $this->getExtraConfig()['docker']['build']['image'] ?? '';
        if (empty($imageName)) {
            throw new \Exception('Empty Docker image name! Set it in build config.');
        }
        /** @var $this \RoboFile */
        $this->taskDockerBuild()
            ->enableBuildKit()
            ->rawArg('--ssh default=' . $_SERVER['HOME'] .'/.ssh/id_rsa')
            ->tag($imageName)
            ->run();

        if (!empty($opts['ver']) && $opts['autover']) { // Autoversion
            // if tag v1.1.0 is given, it will also tag v1 and v1.1
            // if tag v1.1 is given, it will also tag v1
            if (!preg_match('/^(?<major>v?\d)(\.(?<minor>\d))?(\.(?<patch>\d+))?$/', $opts['ver'], $matches)) {
                throw new \Exception(
                    sprintf('Version format "%s" is not compatible with "autover" option!', $opts['ver'])
                );
            }

            $oldImageName = $imageName;
            $imageNameBase = explode(':', $oldImageName)[0];

            $imageNameAliases = [];
            if (isset($matches['patch'])) {
                $tag = $matches['major'] . '.' . $matches['minor'] . '.' . $matches['patch'];
                $imageNameAliases[] = $imageNameBase . ':' . $tag;
            }
            if (isset($matches['minor'])) {
                $tag = $matches['major'] . '.' . $matches['minor'];
                $imageNameAliases[] = $imageNameBase . ':' . $tag;
            }
            if (isset($matches['major'])) {
                $tag = $matches['major'];
                $imageNameAliases[] = $imageNameBase . ':' . $tag;
            }

            foreach ($imageNameAliases as $newImageName) {
                exec('docker tag ' . $oldImageName . ' ' . $newImageName);
            }
            if ($opts['push']) {
                foreach ($imageNameAliases as $newImageName) {
                    exec('docker push ' . $newImageName);
                }
            }

            return;
        }

        if (!empty($opts['ver'])) {
            $oldImageName = $imageName;
            $imageNameBase = explode(':', $oldImageName)[0];
            $imageName = $imageNameBase . ':' . $opts['ver'];
            exec('docker tag ' . $oldImageName . ' ' . $imageName);
        }
        if ($opts['push']) {
            exec('docker push ' . $imageName);
        }
    }
}
