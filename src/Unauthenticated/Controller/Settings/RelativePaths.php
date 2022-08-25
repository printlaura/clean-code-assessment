<?php

namespace App\Unauthenticated\Controller\Settings;

/**
 * Stores global variables used everywhere in the project.
 */
class RelativePaths
{


    /**
     * Returs correct slash type for the current operating system
     *
     * @return string
     */
    private static function getSlashType(): string
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows
            return "\\";
        } else {
            // not Windowns
            return "/";
        }
    }


    /**
     * Returns the absolute path to a file.
     *
     * @param string $pathFromRoot must be path from root directory of this repository starting with a slash
     *
     * @return string corrected for operating system
     */
    public static function getAbsolutePathTo(string $pathFromRoot): string
    {
        $path = self::getRepositoryRootPath().$pathFromRoot;
        if (self::getSlashType() === "/") {
            return str_replace("\\", "/", $path);
        } else {
            return str_replace("/", "\\", $path);
        }
    }


    /**
     * Gets absolute path to the root directory of this repository.
     *
     * @return string
     */
    public static function getRepositoryRootPath(): string
    {
        return dirname(getcwd());
    }


    /**
     * Gets the name of the root directory of this repository.
     *
     * @return string
     */
    public static function getRootDirName(): string
    {
        $stepsToRoot = 5;
        $dir         = str_replace(dirname(__DIR__, $stepsToRoot), '', dirname(__DIR__, ($stepsToRoot - 1)));
        return str_replace("\\", "", str_replace("/", "", $dir));
    }


    /**
     * Returns url up to the rood directory name
     *
     * @return string
     */
    public static function getBaseUrl(): string
    {
        $currentUrl = (isset($_SERVER['HTTPS']) === true && $_SERVER['HTTPS'] === 'on' ? "https" : "http")."://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $cutoff     = (strpos($currentUrl, self::getRootDirName()) + strlen(self::getRootDirName()));
        return substr($currentUrl, 0, $cutoff);
    }


}
