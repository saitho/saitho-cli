<?php
namespace Saitho\CLI\Robo\Utility;

class Config
{
    public function getExtraConfig(string $name): array
    {
        $content = file_get_contents('composer.json');
        $json = json_decode($content, true);
        $extraConfig = $json['extra'] ?? [];

        if (empty($name)) {
            return $extraConfig;
        }
        return $extraConfig[$name] ?? [];
    }
}
