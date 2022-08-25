<?php

namespace App\Unauthenticated\Model\Models\Media;

use App\Unauthenticated\Controller\Actions\ActionRequestValidation;
use App\Unauthenticated\Controller\Settings\Settings as Settings;
use App\Unauthenticated\Model\Model;
use App\Unauthenticated\Model\Models\Language\LanguageFileModel;
use BadFunctionCallException;
use InvalidArgumentException;

/**
 * Media Reference model
 */
class MediaReferenceModel extends Model
{

    /**
     * Variable for the id
     *
     * @var integer
     */
    public int $id;

    /**
     * Can be either 'image' or 'video'
     *
     * @var string|null
     */
    public ?string $type;

    /**
     * Variable for the source
     *
     * @var string
     */
    public string $source;


    /**
     * Referencing a media
     *
     * @param string      $source database the media object is in, necessary because media ids are not unique
     * @param int         $id     reference to the media object that will be moved
     * @param string|null $type   if the media is an image 'I' or video 'V'. (also accepts 'image' or 'video')
     */
    public function __construct(string $source, int $id, ?string $type = null)
    {
        if (in_array($source, ActionRequestValidation::SOURCES) === false) {
            throw new InvalidArgumentException("source: expected one of [".join(", ", ActionRequestValidation::SOURCES)."] but received $source");
        }
        $this->source = utf8_encode($source);
        $this->id     = $id;
        if (isset($type) === true) {
            $this->setType($type);
        }
    }


    /**
     * String representation of the object, for easier debugging
     *
     * @return string
     */
    public function __toString()
    {
        if (isset($this->type) === true) {
            return "MediaReference($this->source, $this->id, $this->type)";
        } else {
            return "MediaReference($this->source, $this->id)";
        }
    }


    /**
     * Returns true if this media has the type image
     *
     * @return bool true when image
     * @throws BadFunctionCallException if type is not set
     */
    public function isImage(): bool
    {
        $this->assertType();
        return $this->type === 'image';
    }


    /**
     * Returns the media type as 'I' if image or 'V' if video
     *
     * @return string can be 'I' or 'V'
     * @throws BadFunctionCallException if type is not set
     */
    public function getTypeAsIV(): string
    {
        if ($this->isImage() === true) {
            return 'I';
        }
        return 'V';
    }


    /**
     * Returns true when source and id are the same. Ignores the type.
     *
     * @param MediaReferenceModel $mediaReferenceModel
     *
     * @return bool
     */
    public function equals(MediaReferenceModel $mediaReferenceModel): bool
    {
        if ($this->source !== $mediaReferenceModel->source) {
            return false;
        }
        if ($this->id !== $mediaReferenceModel->id) {
            return false;
        }
         return true;
    }


    /**
     * Throws exception, when type is not set.
     *
     * @return bool true when type is set
     * @throws BadFunctionCallException if type is not set
     */
    public function assertType(): bool
    {
        if (isset($this->type) === false) {
            throw new BadFunctionCallException("type is not set");
        }
        return true;
    }


    /**
     * Return media thumbnail link
     *
     * @param string $language 'en' or 'de'
     *
     * @return string link
     */
    public function getThumb(string $language): string
    {
        if ($this->isImage() === true) {
            return $this->getBaseUrlImage($language)."/s.jpg";
        } else {
            return $this->getBaseUrlVideo($language)."/spr.jpg";
        }
    }


    /**
     * Return media source link
     *
     * @param string $language 'en' or 'de'
     *
     * @return string link
     */
    public function getSrc(string $language): string
    {
        if ($this->isImage() === true) {
            return $this->getBaseUrlImage($language)."/m.jpg";
        } else {
            return $this->getBaseUrlVideo($language)."/mpr.mp4";
        }
    }


    /**
     * Return media preview link, only available for video
     *
     * @param string $language 'en' or 'de'
     *
     * @return string link for video, or null
     */
    public function getPrev(string $language): ?string
    {
        if ($this->isImage() === true) {
            return null;
        } else {
            return $this->getBaseUrlVideo($language)."/spr.mp4";
        }
    }


    /**
     * Returns base url for image links
     *
     * @param string $language 'en' or 'de'
     *
     * @return string
     */
    private function getBaseUrlImage(string $language): string
    {
        if ($language === "en") {
            return Settings::BASE_URL_EN."/bild/".$this->source."/".$this->id;
        } else {
            return Settings::BASE_URL_DE."/bild/".$this->source."/".$this->id;
        }
    }


    /**
     * Returns base url for video links
     *
     * @param string $language 'en' or 'de'
     *
     * @return string
     */
    private function getBaseUrlVideo(string $language): string
    {
        if ($language === "en") {
            $videoUrl = Settings::BASE_URL_EN."/videos/";
        } else {
            $videoUrl = Settings::BASE_URL_DE."/videos/";
        }

        $mediaIdStringLen = strlen($this->id);

        if (($mediaIdStringLen % 2) !== 0) {
            $mediaIdString    = "0".$this->id;
            $mediaIdStringLen = ($mediaIdStringLen + 1);
        } else {
            $mediaIdString = (string) $this->id;
        }
        for ($i = 0; $i < $mediaIdStringLen; $i = ($i + 2)) {
            $videoUrl .= $mediaIdString[$i].$mediaIdString[($i + 1)];
            if ($i < ($mediaIdStringLen - 2)) {
                $videoUrl .= "/";
            }
        }
        return $videoUrl;
    }


    /**
     * Set Media Type, must be I, V, image or video
     *
     * @param string $type string to get type from
     *
     * @return void
     */
    public function setType(string $type)
    {
        $type = strtolower($type);
        if ($type === 'i') {
            $this->type = utf8_encode('image');
        } else if ($type === 'image') {
            $this->type = utf8_encode('image');
        } else if ($type === 'v') {
            $this->type = utf8_encode('video');
        } else if ($type === 'video') {
            $this->type = utf8_encode('video');
        } else {
            throw new InvalidArgumentException("unexpected type '$type' must be one of ['i', 'v', 'image', 'video']");
        }
    }


    /**
     * Get the long Media Type
     *
     * @param string $type     string to get type from
     * @param string $language language
     *
     * @return void
     */
    public function getMediaTypeInLanguage(string $type, string $language): string
    {
        $languageFile    = new LanguageFileModel($language);
        $languageContent = $languageFile->read();

        $type = strtolower($type);
        if ($type === 'i') {
            $output = $languageContent["mediatype"]["image"];
        } else if ($type === 'image') {
            $output = $languageContent["mediatype"]["image"];
        } else if ($type === 'v') {
            $output = $languageContent["mediatype"]["video"];
        } else if ($type === 'video') {
            $output = $languageContent["mediatype"]["video"];
        } else {
            throw new InvalidArgumentException("unexpected type '$type' must be one of ['i', 'v', 'image', 'video']");
        }

        return $output;
    }


}
