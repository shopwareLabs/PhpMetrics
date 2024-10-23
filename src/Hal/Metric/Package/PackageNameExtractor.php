<?php

namespace Hal\Metric\Package;

class PackageNameExtractor
{
    public function __construct(
        private readonly int $packageLevelLimit = 0,
    ) { }

    public function getPackageFromClassName(string $className): string
    {
        if (strpos($className, '\\') === false) {
            return '\\';
        }

        $parts = explode('\\', $className);
        array_pop($parts);
        if ($this->packageLevelLimit > 0) {
            $parts = array_slice($parts, 0, $this->packageLevelLimit);
        }
        return implode('\\', $parts) . '\\';
    }

    public function getPackageFromNamespace(string $namespace): string
    {
        $package = $namespace;
        if ($this->packageLevelLimit > 1) {
            $package = implode('\\', array_slice(
                explode('\\', $namespace),
                0, $this->packageLevelLimit
            ));
        }

        return $package . '\\';
    }
}
