<?php

namespace App\Visiting\Model\Models\Folder;

use App\Unauthenticated\Model\CustomDateTimeModel;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderSelect;
use App\Unauthenticated\Model\Model;
use App\Unauthenticated\Model\Models\Media\MediaModel;
use App\Unauthenticated\Model\Models\Media\PreviewModel;
use InvalidArgumentException;

/**
 * Folder class
 */
class FolderModel extends Model
{

    /**
     * Variable for the name
     *
     * @var string
     */
    public string $name;

    /**
     * Variable for the description
     *
     * @var string|null
     */
    public ?string $description;
    
    /**
     * Variable for the previews
     *
     * @var PreviewModel[] |null
     */
    public ?array $previews;

    /**
     * Variable for the Media
     *
     * @var MediaModel[] |null
     */
    public ?array $medias;

    /**
     * Variable for the viewhash
     *
     * @var string|null
     */
    public ?string $viewhash;

    /**
     * Variable for the edithash
     *
     * @var string|null
     */
    public ?string $edithash;

    /**
     * Variable for the created
     *
     * @var CustomDateTimeModel
     */
    public CustomDateTimeModel $created;

    /**
     * Variable for the changed
     *
     * @var CustomDateTimeModel
     */
    public CustomDateTimeModel $changed;

    /**
     * Variable for the picturecount
     *
     * @var integer
     */
    public int $picturecount;

    /**
     * Variable for the videocount
     *
     * @var integer
     */
    public int $videocount;

    /**
     * Variable for the commentcount
     *
     * @var integer
     */
    public int $commentscount;

    /**
     * Variable for the sharecount
     *
     * @var integer
     */
    public int $sharecount;

    /**
     * Variable for the owner
     *
     * @var string
     */
    public string $owner;

    /**
     * Variable for the rights
     *
     * @var string
     */
    public string $rights;

    /**
     * Variable for the sortBy
     *
     * @var string|null
     */
    public ?string $sortBy;

    /**
     * Variable for the sortOrder
     *
     * @var string|null
     */
    public ?string $sortOrder;

    /**
     * Variable for the limit
     *
     * @var integer|null
     */
    public ?int $limit;

    /**
     * Variable for the offset
     *
     * @var integer|null
     */
    public ?int $offset;


    /**
     * Constructor define the folder model
     *
     * @param string              $name          name
     * @param string|null         $description   description
     * @param string|null         $viewhash      viewhash
     * @param string|null         $edithash      edithash
     * @param CustomDateTimeModel $created       created date
     * @param CustomDateTimeModel $changed       changed date
     * @param int                 $pictureCount  pictureCount
     * @param int                 $videoCount    videoCount
     * @param int                 $commentsCount commentCount
     * @param int                 $shareCount    shareCount
     * @param string              $owner         owner
     * @param string              $rights        rights
     * @param string|null         $sortBy        sortBy
     * @param string|null         $sortOrder     sortOrder
     * @param int|null            $limit         limit
     * @param int|null            $offset        offset
     * @param array|null          $previews      previews
     * @param array|null          $medias        medias
     *
     * @throws InvalidArgumentException Either Previews or Medias must contain a value
     */
    public function __construct(
        string              $name,
        ?string             $description,
        ?string             $viewhash,
        ?string             $edithash,
        CustomDateTimeModel $created,
        CustomDateTimeModel $changed,
        int                 $pictureCount,
        int                 $videoCount,
        int                 $commentsCount,
        int                 $shareCount,
        string              $owner,
        string              $rights,
        ?string             $sortBy = null,
        ?string             $sortOrder = null,
        ?int                $limit = null,
        ?int                $offset = null,
        ?array         $previews = null,
        ?array         $medias = null
    ) {
        $this->name          = utf8_encode($name);
        $this->description   = utf8_encode($description);
        $this->previews      = $previews;
        $this->medias        = $medias;
        $this->viewhash      = $viewhash;
        $this->edithash      = $edithash;
        $this->created       = $created;
        $this->changed       = $changed;
        $this->picturecount  = $pictureCount;
        $this->videocount    = $videoCount;
        $this->commentscount = $commentsCount;
        $this->sharecount    = $shareCount;
        $this->owner         = $owner;
        $this->rights        = $rights;
        $this->sortBy        = $sortBy;
        $this->sortOrder     = $sortOrder;
        $this->limit         = $limit;
        $this->offset        = $offset;
        if ($previews !== null && $medias !== null) {
            throw new InvalidArgumentException("Either Previews or Medias must contain a value");
        }
    }


    /**
     * SQL Query of all the users with access to folder
     *
     * @param int $folderId folder where users have access to
     *
     * @return QueryBuilderSelect sql query
     */
    public static function getUsersQuery(int $folderId): QueryBuilderSelect
    {
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select("user_id");
        $queryBuilder->from("web_lb_folder_user");
        $queryBuilder->andWhereEqualsInt("folder_id", $folderId);
        $queryBuilder->andWhereEqualsBool("visible", true);
        return $queryBuilder;
    }


    /**
     * Set rights after constructions
     *
     * @param string $rights right string must be view, edit or owner
     *
     * @return void
     * @throws InvalidArgumentException thrown when $rights is not one of view, edit or owner
     */
    public function setRights(string $rights)
    {
        if (in_array($rights, ['view', 'edit', 'owner']) === true) {
            $this->rights = $rights;
        } else {
            throw new InvalidArgumentException("must be one of ['view', 'edit', 'owner']");
        }
    }


}
