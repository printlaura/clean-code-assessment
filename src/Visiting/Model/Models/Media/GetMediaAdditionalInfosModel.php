<?php

namespace App\Visiting\Model\Models\Media;

use App\Unauthenticated\Model\Model;
use App\Unauthenticated\Model\Models\Media\MediaReferenceModel;
use App\Visiting\Model\Models\Comment\ShowCommentsModel;
use Psr\Log\LoggerInterface;
use App\Visiting\Model\Models\Description\DescriptionModel;

/**
 * Class for getting the additional infos about a media which is in a folder
 */
class GetMediaAdditionalInfosModel extends Model
{

    /**
     * Used for logging information.
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;


    /**
     * Create a new model instance.
     *
     * @param LoggerInterface $logger logger reference
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }


    /**
     * Get all media in a folder from the database.
     *
     * @param int                 $folderId       folder the media is in
     * @param MediaReferenceModel $mediaReference media to get additional information on
     *
     * @return MediaAdditionalInfosModel
     */
    public function get(int $folderId, MediaReferenceModel $mediaReference): MediaAdditionalInfosModel
    {
            // get comment count
            $showCommentsModel = new ShowCommentsModel($this->logger);
            $mediaCommentCount = $showCommentsModel->getMediaCommentCount($folderId, $mediaReference);
            // get description
            $mediaDescription = DescriptionModel::get($this->logger, $folderId, $mediaReference);

            return new MediaAdditionalInfosModel(
                $mediaReference->id,
                $mediaReference->source,
                $mediaCommentCount,
                $mediaDescription
            );
    }


}
