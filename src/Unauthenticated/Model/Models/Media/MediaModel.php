<?php

namespace App\Unauthenticated\Model\Models\Media;

use App\Unauthenticated\Model\CustomDateTimeModel;
use App\Unauthenticated\Model\Model;
use InvalidArgumentException;

/**
 * Media class
 */
class MediaModel extends Model
{

    /**
     * Variable for the id
     *
     * @var integer
     */
    public int $mediaid;

    /**
     * Can be either 'image' or 'video'
     *
     * @var string
     */
    public string $mediatype;

    /**
     * Variable for the mediaThumb
     *
     * @var string
     */
    public string $mediathumb;

    /**
     * Variable for the mediaPrev
     *
     * @var string|null
     */
    public ?string $mediaprev;

    /**
     * Variable for the mediaSrc
     *
     * @var string
     */
    public string $mediasrc;

    /**
     * Variable for the source
     *
     * @var string
     */
    public string $source;

    /**
     * Variable for the sort
     *
     * @var integer|null
     */
    public ?int $sort;
    
    /**
     * Variable for the title
     *
     * @var string|null
     */
    public ?string $title;

    /**
     * Variable for the caption
     *
     * @var string
     */
    public string $caption;

    public int $height;

    public int $width;

    /**
     * Variable for the commentCount
     *
     * @var integer|null
     */
    public ?int $commentscount;

    /**
     * Description written by the user
     *
     * @var string|null
     */
    public ?string $notice;

    /**
     * Variable for the licenseType
     *
     * @var string
     */
    public string $licensetype;

    /**
     * Variable for the licenceLanguagePointer
     *
     * @var string
     */
    public string $licencelanguagepointer;

    /**
     * Variable for the licenceLanguagePointer
     *
     * @var UsageLicensesModel[]
     */
    public array $usagelicences;

    /**
     * Variable for the cliplength
     *
     * @var string
     */
    public string $cliplength;

    /**
     * Variable for the master image flagg
     *
     * @var int|null
     */
    public ?int $masterImage;

    /**
     * Date and time of the media creation
     *
     * @var CustomDateTimeModel
     */
    public CustomDateTimeModel $creationdate;

    /**
     * Variable for the author
     *
     * @var string
     */
    public string $author;


    /**
     * Constructor define the Media model
     *
     * @param MediaReferenceModel      $mediaReference         unique reference to media, must include type
     * @param string                   $mediaThumb             link to the media thumbnail
     * @param string                   $mediaSrc               link to the media source
     * @param int|null                 $sort                   sort position in the current folder, needs to be set
     * @param string|null              $title                  title for the media
     * @param string                   $caption                caption for the media
     * @param int|null                 $commentCount           comment count for the media, needs to be set
     * @param string|null              $notice                 description written by user, needs to be set
     * @param int                      $height
     * @param int                      $width
     * @param string                   $licenseType            license type of the media
     * @param string                   $licenceLanguagePointer license language pointer to the json in react
     * @param UsageLicensesModel[]     $usageLicences          usage licenses
     * @param string                   $author                 author of the media
     * @param string                   $clipLength             price of the media
     * @param CustomDateTimeModel      $creationDate           datetime of media creation
     * @param MediaReferenceModel|null $masterMediaReference   if this media is a master image or not
     * @param string|null              $mediaPrev              link to the media preview, only when $type is "image"
     */
    public function __construct(
        MediaReferenceModel  $mediaReference,
        string               $mediaThumb,
        string               $mediaSrc,
        ?int                  $sort,
        ?string              $title,
        string               $caption,
        ?int                  $commentCount,
        ?string               $notice,
        int                  $height,
        int                  $width,
        string               $licenseType,
        string               $licenceLanguagePointer,
        array                $usageLicences,
        string               $author,
        string               $clipLength,
        CustomDateTimeModel  $creationDate,
        ?MediaReferenceModel $masterMediaReference = null,
        ?string              $mediaPrev = null
    ) {
        $mediaReference->assertType();
        $this->mediaid       = $mediaReference->id;
        $this->mediatype     = $mediaReference->type;
        $this->mediathumb    = $mediaThumb;
        $this->mediasrc      = $mediaSrc;
        $this->source        = $mediaReference->source;
        $this->sort          = $sort;
        $this->title         = utf8_encode($title);
        $this->caption       = utf8_encode($caption);
        $this->commentscount = $commentCount;
        $this->notice        = $notice;
        $this->height        = $height;
        $this->width         = $width;
        $this->licensetype   = $licenseType;
        $this->licencelanguagepointer = $licenceLanguagePointer;
        $this->usagelicences          = $usageLicences;
        $this->author       = $author;
        $this->cliplength   = $clipLength;
        $this->masterImage  = $masterMediaReference;
        $this->creationdate = $creationDate;
        // check values
        if ($mediaReference->isImage() === true) {
            $this->mediaprev = null;
        } else {
            if ($mediaPrev === null) {
                throw new InvalidArgumentException("Video must contain mediaPrev");
            }
            $this->mediaprev = $mediaPrev;
        }
    }

    
    /**
     * SetCommentCount
     *
     * @param int $commentsCount
     *
     * @return void
     */
    public function setCommentCount(int $commentsCount)
    {
        $this->commentscount = $commentsCount;
    }

    
    /**
     * SetNotice
     *
     * @param string $notice
     *
     * @return void
     */
    public function setNotice(string $notice)
    {
        $this->notice = $notice;
    }

    
    /**
     * SetSort
     *
     * @param int $sort
     *
     * @return void
     */
    public function setSort(int $sort)
    {
        $this->sort = $sort;
    }


}
