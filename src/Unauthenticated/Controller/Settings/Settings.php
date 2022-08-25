<?php

declare(strict_types=1);

namespace App\Unauthenticated\Controller\Settings;

/**
 * Settings Class
 */
class Settings implements SettingsInterface
{
    /**
     * When this is true, cache is being created. And debug errors are being ignored
     */
    const IS_PRODUCTION = true;

    /**
     * Base URL for the com domain
     */
    const BASE_URL_EN = "https://www.imago-images.com";

    /**
     * Base URL for the de domain
     */
    const BASE_URL_DE = "https://www.imago-images.de";

    const HTTP_AUTH_USER = "reactfrontend";

    const HTTP_AUTH_PASS = "twjxWJ9dEWVhk5BdnQDXkhZ8NNK3veCQDGt4FB3ymACKSppMaqkv2keRR9ejjeng7TpM2nKrMenZxh6j";

    /**
     * Collection of settings, with string key
     *
     * @var array
     */
    private array $settings;


    /**
     * Settings constructor.
     *
     * @param array $settings array of all settings, with string key
     */
    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }


    /**
     * Gets setting by key
     *
     * @param string $key setting reference
     *
     * @return mixed settings
     */
    public function get(string $key = '')
    {
        return (empty($key) === true) ? $this->settings : $this->settings[$key];
    }


}
