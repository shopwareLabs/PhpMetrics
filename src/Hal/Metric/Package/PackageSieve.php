<?php

namespace Hal\Metric\Package;

class PackageSieve
{
    public function __construct(
        private readonly int $packageLevelLimit = 0,
        private readonly array $ignorePrefixes = [],
    )
    {
    }

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

    public function excludePackage(string $namespace): bool
    {
        foreach ($this->ignorePrefixes as $prefix) {
            if (str_starts_with($namespace, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
