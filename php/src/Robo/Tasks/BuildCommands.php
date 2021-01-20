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

    function buildDocker($opts = ['push' => false])
    {
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

        if ($opts['push']) {
            exec('docker push ' . $imageName);
        }
    }
}
