<?php

namespace Hal\Metric\Package;

class PackageNameExtractor
{
    private const PACKAGE_LEVEL_LIMIT = 4;

    public static function getPackageFromClassName(string $className): string
    {
        if (strpos($className, '\\') === false) {
            return '\\';
        }

        $parts = explode('\\', $className);
        array_pop($parts);
        return implode('\\', array_slice($parts, 0, 3)) . '\\';
    }

    public static function getPackageFromNamespace(string $namespace): string
    {
        $package = implode('\\', array_slice(
            explode('\\', $namespace),
            0, self::PACKAGE_LEVEL_LIMIT
        ));
        return $package . '\\';
    }
}
