<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

/**
 * Test to enforce security levels.
 * Security levels are created to separate Unauthenticated api calls from api calls that need the user to be authenticated.
 * Unauthenticated - user does not need to sign in, everyone can access this
 * Visiting - user must know a secret hash
 * Authenticated - user must be authenticated
 * A namespace of higher security level can use anything from a namespace with lower security level, but not the otherway around.
 */
class SecurityLevelTest extends TestCase
{
    const UNAUTHENTICATED_DIR       = __DIR__."/../src/Unauthenticated";
    const UNAUTHENTICATED_NAMESPACE = "App\\Unauthenticated";
    const VISITING_DIR            = __DIR__."/../src/Visiting";
    const VISITING_NAMESPACE      = "App\\Visiting";
    const AUTHENTICATED_DIR       = __DIR__."/../src/Authenticated";
    const AUTHENTICATED_NAMESPACE = "App\\Authenticated";


    /**
     * Test the Unauthenticated namespace for any usage of Authenticated or Visiting namespace
     *
     * @return void
     */
    public function testUnauthenticatedNamespaceDoesNotUseAuthenticated()
    {
        $apiFiles = self::getFilesInDir(self::UNAUTHENTICATED_DIR);
        foreach ($apiFiles as $fileName) {
            $fileContent = file_get_contents($fileName);
            // tests
            $pos = strpos($fileContent, "use ".self::AUTHENTICATED_NAMESPACE);
            $this->assertFalse(
                $pos,
                "Found Authenticated usage in Unauthenticated namespace. In $fileName on char $pos .This is not allowed, due to security levels."
            );
            $pos = strpos($fileContent, "use ".self::VISITING_NAMESPACE);
            $this->assertFalse(
                strpos($fileContent, "use ".self::VISITING_NAMESPACE),
                "Found Visiting usage in Unauthenticated namespace. In $fileName on char $pos . This is not allowed, due to security levels."
            );
        }
    }


    /**
     * Test the Visiting namespace for any usage of Authenticated namespace
     *
     * @return void
     */
    public function testVisitngNamespaceDoesNotUseAuthenticated()
    {
        $apiFiles = self::getFilesInDir(self::VISITING_DIR);
        foreach ($apiFiles as $fileName) {
            $fileContent = file_get_contents($fileName);
            // tests
            $pos = strpos($fileContent, "use ".self::AUTHENTICATED_NAMESPACE);
            $this->assertFalse(
                $pos,
                "Found Authenticated usage in Visiting namespace. In $fileName on char $pos . This is not allowed, due to security levels."
            );
        }
    }


    /**
     * Returns all files in a directory and its subdirecories recursivly.
     *
     * @param string $dir     directory to be searched
     * @param array  $results used for recursion
     *
     * @return array array of string filenames
     */
    private static function getFilesInDir(string $dir, array &$results = []): array
    {
        $files = scandir($dir);

        foreach ($files as $key => $value) {
            $path = realpath($dir.DIRECTORY_SEPARATOR.$value);
            if (is_dir($path) === false) {
                $results[] = $path;
            } else if ($value !== "." && $value !== "..") {
                self::getFilesInDir($path, $results);
            }
        }
        return $results;
    }


}
