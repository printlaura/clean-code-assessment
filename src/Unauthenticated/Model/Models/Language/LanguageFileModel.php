<?php

namespace App\Unauthenticated\Model\Models\Language;

use App\Unauthenticated\Controller\Settings\RelativePaths;
use App\Unauthenticated\Model\Model;

/**
 * Class for read the language file into an array
 */
class LanguageFileModel extends Model
{
    
    /**
     * Variable for the language
     *
     * @var string
     */
    private string $language;


    /**
     * Create a new instance.
     *
     * @param string $language 'en' or default (de)
     */
    public function __construct(string $language)
    {
        $this->language = $language;
    }


    /**
     * Read the language file into an array
     *
     * @return array with all language fields
     */
    public function read(): array
    {
        if ($this->language === 'de') {
            $languageFile = RelativePaths::getAbsolutePathTo("/src/language/de/de.json");
        } else {
            $languageFile = RelativePaths::getAbsolutePathTo("/src/language/en/en.json");
        }

        return json_decode(file_get_contents($languageFile), true);
    }


}
