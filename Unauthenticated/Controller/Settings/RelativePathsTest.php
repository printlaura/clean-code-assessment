<?php

namespace Tests\Unauthenticated\Controller\Settings;

use App\Unauthenticated\Controller\Settings\Settings;
use App\Unauthenticated\Controller\Settings\RelativePaths;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the variables class that is storing global variables
 */
class RelativePathsTest extends TestCase
{


    /**
     * Tests relative paths on production and test environment
     *
     * @return void
     */
    public function testGetRepositoryRoot()
    {
        $path = RelativePaths::getRepositoryRootPath();
        if (Settings::IS_PRODUCTION === true) {
            $this->assertEquals("\\\\web2002\Webseiten\\externe Webseiten\\api-dev.imago-images.com\\imago-api", $path);
        } else {
            $this->assertEquals("\\\\webdev2.imago.de\\Webseiten\imago-api\\api-db.dev.local\\imago-api", $path);
        }
    }


    /**
     * Tests root directory name on production and test environment
     *
     * @return void
     */
    public function testGetRootDirName()
    {
        $dirName = RelativePaths::getRootDirName();
        $this->assertEquals("imago-api", $dirName);
    }


}
