<?php

namespace App\Authenticated\Model\Models\Share;

use App\Authenticated\Model\Models\Share\GetFolderIdAndVisitorStatusModel as GFIDVS;
use App\Unauthenticated\Controller\Settings\RelativePaths;
use App\Unauthenticated\Controller\Settings\Settings as Settings;
use App\Unauthenticated\Model\DatabaseConnector\Database;
use App\Unauthenticated\Model\DatabaseConnector\QueryBuilderSelect;
use App\Unauthenticated\Model\Model;
use App\Unauthenticated\Model\Models\Language\LanguageFileModel;
use App\Visiting\Exceptions\FolderUnknownException;
use PHPMailer\PHPMailer\Exception as MailerException;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Log\LoggerInterface;

/**
 * Send an e-mail to a user with a link to a folder
 */
class EmailToSendModel extends Model
{

    private const MAIL_SERVER   = "wp13005202.mailout.server-he.de";
    private const MAIL_PORT     = 25;
    private const MAIL_USER     = "wp13005202-websmtp1";
    private const MAIL_PASSWORD = "cmeiUnbwdcKn";

    /**
     * Variable for the logger
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Variable for the email
     *
     * @var string
     */
    public string $email;

    /**
     * Variable for the status
     *
     * @var string
     */
    public string $status;

    /**
     * Variable for the statusMessageCode
     *
     * @var string
     */
    public string $statusMessageCode = "";

    /**
     * Variable for the message
     *
     * @var string
     */
    private string $message;

    /**
     * Variable for the subject
     *
     * @var string
     */
    private string $subject;

    /**
     * Variable for the rightsView
     *
     * @var string
     */
    private string $rightsView;

    /**
     * Variable for the rightsEdit
     *
     * @var string
     */
    private string $rightsEdit;

    /**
     * Variable for the baseUrl
     *
     * @var string
     */
    private string $baseUrl;

    /**
     * Variable for the salutationmen
     *
     * @var string
     */
    private string $salutationmen;

    /**
     * Variable for the salutationwomen
     *
     * @var string
     */
    private string $salutationwomen;


    /**
     * Constructor for initializing email
     *
     * @param LoggerInterface $logger   logger reference
     * @param string          $language language
     *
     * @return void
     */
    public function __construct(LoggerInterface $logger, string $language)
    {
        $this->logger = $logger;

        // get message & subject in correct language
        $languageFile  = new LanguageFileModel($language);
        $languageArray = $languageFile->read();

        $this->message         = $languageArray["email"]["message"];
        $this->subject         = $languageArray["email"]["subject"];
        $this->rightsView      = $languageArray["email"]["viewrights"];
        $this->rightsEdit      = $languageArray["email"]["editrights"];
        $this->salutationmen   = $languageArray["email"]["salutationmen"];
        $this->salutationwomen = $languageArray["email"]["salutationwomen"];

        if ($language === 'en') {
            $this->baseUrl = Settings::BASE_URL_EN;
        } else {
            $this->baseUrl = Settings::BASE_URL_DE;
        }
    }


    /**
     * Send the email
     *
     * @param string $emailAdress email adress to send the email to
     * @param string $hash        folder view hash to build link
     * @param int    $userId      user reference to get information for email content
     *
     * @return void
     * @throws FolderUnknownException
     */
    public function send(string $emailAdress, string $hash, int $userId)
    {
        try {
            // set up mailer
            $phpMailer = new PHPMailer(true);
            $phpMailer->isSMTP();
            // phpcs:disable
            $phpMailer->Host      = self::MAIL_SERVER;
            $phpMailer->SMTPAuth  = true;
            $phpMailer->Username  = self::MAIL_USER;
            $phpMailer->Password  = self::MAIL_PASSWORD;
            $phpMailer->Port      = self::MAIL_PORT;
            // $phpMailer->SMTPDebug = SMTP::DEBUG_LOWLEVEL;

            $message = $this->message;

            // pruefen ob hash edit oder view ist und Ordnernamen abfragen
            // mit E-Mail Anrede Nachname Vorname abfrage
            // mit userid einwahl und firma abfragen
            $database  = new Database($this->logger);
            
            list($folderId, $visitor) = GFIDVS::getFolderIdAndVisitorStatus($hash, $database);
            if ($folderId === 0) {
                throw new FolderUnknownException($folderId);
            }
            $folderName = self::getFolderName($folderId, $database);
            $userInfoReceiver = self::getUserInfoByEmailAdress($emailAdress, $database);
            $userInfoSender   = self::getUserInfoById($userId, $database);

            if ($visitor === 'V') {
                $rights = $this->rightsView;
            } else {
                $rights = $this->rightsEdit;
            }

            if ($userInfoReceiver["sex"] === 'm') {
                $salutation =  $this->salutationmen;
            } elseif ($userInfoReceiver["sex"] === 'f') {
                $salutation =  $this->salutationwomen;
            } else {
                $salutation =  "";
            }

            $link = $this->baseUrl."/lightbox/shared/".$hash;
            // <Anrede> <Vorname> <Nachname> <Einwahl> (<Firma>) <>
            $message = str_replace("##Link##"     ,$link, $message);
            $message = str_replace("##Rechte##"   ,$rights, $message);
            $message = str_replace("##Ordner##"   ,$folderName, $message);
            $message = str_replace("##Anrede##"   ,$salutation, $message);
            $message = str_replace("##Vorname##"  ,$userInfoReceiver["first"], $message);
            $message = str_replace("##Nachname##" ,$userInfoReceiver["last"], $message);
            $message = str_replace("##Einwahl##"  ,$userInfoSender["username"], $message);
            $message = str_replace("##Firma##"    ,$userInfoSender["company"], $message);

            // print_r($message);
            // exit();

            // add content
            $phpMailer->CharSet   = 'UTF-8';
            $phpMailer->Encoding  = 'base64';
            $phpMailer->setFrom('noreply@imago-images.de', 'IMAGO');
            $phpMailer->addAddress($emailAdress);
            $phpMailer->Subject = $this->subject;
            $phpMailer->Body    = $message;

            $phpMailer->IsHTML(true);
            // TODO: Could not find - public\\images\\IMAGO-Primary_Logos-RGB-BLACK-small.png Change relative path
            $phpMailer->AddEmbeddedImage(RelativePaths::getAbsolutePathTo('\public\images\IMAGO-Primary_Logos-RGB-BLACK-small.png'), 'IMAGO-Primary_Logos-RGB-BLACK-small.png');
            $phpMailer->AddEmbeddedImage(RelativePaths::getAbsolutePathTo('\public\images\instagram.png'), 'instagram.png');
            $phpMailer->AddEmbeddedImage(RelativePaths::getAbsolutePathTo('\public\images\facebook.png'), 'facebook.png');
            $phpMailer->AddEmbeddedImage(RelativePaths::getAbsolutePathTo('\public\images\xing.png'), 'xing.png');
            $phpMailer->AddEmbeddedImage(RelativePaths::getAbsolutePathTo('\public\images\linkedin.png'), 'linkedin.png');
            $phpMailer->AddEmbeddedImage(RelativePaths::getAbsolutePathTo('\public\images\imago_twitter.png'), 'imago_twitter.png');
            $phpMailer->AddEmbeddedImage(RelativePaths::getAbsolutePathTo('\public\images\imago_pinterest.png'), 'imago_pinterest.png');
            
            // phpcs:enable
            // send
            $isSuccessful = $phpMailer->send();
            if ($isSuccessful === true) {
                $this->email  = $emailAdress;
                $this->status = "sent";
                // $this->statusMessageCode = $phpMailer->ErrorInfo;
            } else {
                $this->status            = "error";
                $this->statusMessageCode = "unknown error";
            }
        } catch (MailerException $exception) {
            $this->status            = "error";
            $this->statusMessageCode = $exception->getMessage();
        }
    }


    /**
     * Get folder name
     *
     * @param int      $folderId folder id
     * @param Database $database database resource ID
     *
     * @return string folder name
     */
    public static function getFolderName(int $folderId, Database $database): string
    {
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select("name");
        $queryBuilder->from("web_lb_folder");
        $queryBuilder->andWhereEqualsBool("visible", true);
        $queryBuilder->andWhereEqualsInt("id", $folderId);

        $resultFolder = $database->queryPreparedStatement($queryBuilder);

        return $resultFolder[0]->name;
    }


    /**
     * Get User Info using email-address
     *
     * @param string   $emailAdress email adress to get userinfo
     * @param Database $database    database resource ID
     *
     * @return array folder name
     */
    public static function getUserInfoByEmailAdress(string $emailAdress, Database $database): array
    {
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select("anrede, vorname, nachname");
        $queryBuilder->from("vt_webuser");
        $queryBuilder->andWhereEqualsStr("email", $emailAdress);
        // $queryBuilder->andWhereEqualsInt("aktiv", 1);

        $resultFolder = $database->queryPreparedStatement($queryBuilder);
        if (count($resultFolder) > 0) {
            $resultFolder = $resultFolder[0];
            $sex          = $resultFolder->anrede;
            $firstname    = utf8_encode($resultFolder->vorname);
            $lastname     = utf8_encode($resultFolder->nachname);
        } else {
            $sex       = "";
            $firstname = "";
            $lastname  = "";
        }

        return [
            "sex" => $sex,
            "first" => $firstname,
            "last" => $lastname
        ];
    }


    /**
     * Get User Info using user id
     *
     * @param string   $userId   user reference to get userdata
     * @param Database $database database resource ID
     *
     * @return array folder name
     */
    public static function getUserInfoById(string $userId, Database $database): array
    {
        // print_r($userId);
        $queryBuilder = new QueryBuilderSelect();
        $queryBuilder->select("einwahl, firma");
        $queryBuilder->from("vt_webuser");
        $queryBuilder->andWhereEqualsInt("id", $userId);
        // $queryBuilder->andWhereEqualsInt("aktiv", 1);

        $resultFolder = $database->queryPreparedStatement($queryBuilder);
        // print_r($resultFolder);
        if (count($resultFolder) > 0) {
            $resultFolder = $resultFolder[0];
            $username     = utf8_encode($resultFolder->einwahl);
            $company      = utf8_encode($resultFolder->firma);
        } else {
            $username = "";
            $company  = "";
        }

        return [
            "username" => $username,
            "company" => $company
        ];
    }


}
