<?php

namespace App\Visiting\Model\Models\Share;

use App\Unauthenticated\Model\Model;

/**
 * Share model class
 */
class ShareModel extends Model
{

    /**
     * Variable for the username
     *
     * @var string
     */
    public string $username;

    /**
     * Variable for the rights
     *
     * @var string
     */
    public string $rights;


    /**
     * Constructor for initializing email
     *
     * @param string $username username
     * @param string $rights   user rights
     */
    public function __construct(string $username, string $rights)
    {
        $this->username = $username;
        $this->rights   = $rights;
    }


}
