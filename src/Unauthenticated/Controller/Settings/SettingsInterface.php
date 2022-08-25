<?php

declare(strict_types=1);

namespace App\Unauthenticated\Controller\Settings;

/**
 * Settings Interface
 */
interface SettingsInterface
{


    /**
     * Gets setting by key
     *
     * @param string $key setting reference
     *
     * @return mixed settings
     */
    public function get(string $key = '');


}
