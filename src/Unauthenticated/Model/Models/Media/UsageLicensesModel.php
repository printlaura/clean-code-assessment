<?php

namespace App\Unauthenticated\Model\Models\Media;

use App\Unauthenticated\Model\Model;

/**
 * Usage Licenses Model
 */
class UsageLicensesModel extends Model
{

    /**
     * Variable for the languagePointer
     *
     * @var string
     */
    public string $languagePointer;

    /**
     * Variable for the name
     *
     * @var string
     */
    public string $name;

    /**
     * Variable for the credits
     *
     * @var integer
     */
    public int $credits;

    /**
     * Variable for the price
     *
     * @var integer
     */
    public int $price;

    /**
     * Variable for the currency
     *
     * @var string
     */
    public string $currency;


    /**
     * Constructor define the UsageLicenses model
     *
     * @param string $languagePointer language pointer to the json in react
     * @param string $name            short name of the licenses
     * @param int    $credits         credits
     * @param int    $price           price
     * @param string $currency        currency
     */
    public function __construct(string $languagePointer, string $name, int $credits, int $price, string $currency)
    {
        $this->languagePointer = utf8_encode($languagePointer);
        $this->name            = utf8_encode($name);
        $this->credits         = $credits;
        $this->price           = $price;
        $this->currency        = utf8_encode($currency);
    }


}
