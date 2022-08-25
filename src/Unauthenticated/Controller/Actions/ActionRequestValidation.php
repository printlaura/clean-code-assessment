<?php

namespace App\Unauthenticated\Controller\Actions;

use App\Unauthenticated\Exceptions\RequestInValidException;

/**
 * Used to validate variables and json in request. Throws RequestInValidException exception when validation failed
 *  <code>
 * try {
 *  self::isContentValid($content);
 * } catch (RequestInValidException $exception) {
 *  return $response->withStatus(400, $exception->getMessage());
 * }
 * </code>
 */
class ActionRequestValidation
{
    const SOURCES     = [
        'sp',
        'st',
        'pond5',
        'age',
    ];
    const INTEGER_MAX = 2147483647;
    const INTEGER_MIN = -2147483648;


    /**
     * Tests if $haystack contains key and value is an integer
     *
     * @param array  $haystack          request content that should contain integer key
     * @param string $key               that's value must be an integer
     * @param bool   $isNegativeAllowed when false, throws exception on negative integer, default=true
     *
     * @return void
     * @throws RequestInValidException if key does not exist or is not an integer
     */
    public static function containsIntegerValue(array $haystack, string $key, bool $isNegativeAllowed = true)
    {
        self::containsKeys($haystack, 'content', [$key]);

        if (is_integer($haystack[$key]) === false) {
            throw new RequestInValidException("$key must be an integer (number)");
        }
        if ($isNegativeAllowed === false) {
            if ($haystack[$key] < 0) {
                throw new RequestInValidException("$key value must be above zero");
            } else if ($haystack[$key] > self::INTEGER_MAX) {
                throw new RequestInValidException("$key value is too large");
            }
        }
    }


    /**
     * Tests if $haystack contains key and value is an integer with a value above -1
     *
     * @param array  $haystack request content that should contain integer key
     * @param string $key      that's value must be an integer
     *
     * @return void
     * @throws RequestInValidException if key does not exist or is not an integer
     */
    public static function containsPositiveIntegerValue(array $haystack, string $key)
    {
        self::containsIntegerValue($haystack, $key, false);
    }


    /**
     * Tests if $haystack contains keys and their values are integers
     *
     * @param array    $haystack          request content that should contain integer key
     * @param string[] $keys              that values must be integer
     * @param bool     $isNegativeAllowed when false, throws exception on any negative integer, default=true
     *
     * @return void
     * @throws RequestInValidException if key does not exist or is not an integer
     */
    public static function containsIntegerValues(array $haystack, array $keys, bool $isNegativeAllowed = true)
    {
        self::containsKeys($haystack, 'content', $keys);

        foreach ($keys as $key) {
            self::containsIntegerValue($haystack, $key, $isNegativeAllowed);
        }
    }


    /**
     * Tests if content contains all keys
     *
     * @param array|null $haystack request content that should contain all keys
     * @param string     $name     name of the $haystack, will be used in error message
     * @param array      $keys     string array of all the keys that $content should contain
     *
     * @return void
     * @throws RequestInValidException if content does not contain at least one of the keys
     */
    public static function containsKeys(?array $haystack, string $name, array $keys)
    {
        if (isset($haystack) === false) {
            throw new RequestInValidException("Missing key $name");
        }
        foreach ($keys as $key) {
            if (array_key_exists($key, $haystack) === false) {
                throw new RequestInValidException("$name must contain key '$key'");
            }
        }
    }


    /**
     * Tests if $haystack contains a key named 'objecttype' with the values either 'image' or 'video' (isOneOf)
     *
     * @param array  $haystack will be tested for the key and values
     * @param string $key      default is objecttype
     *
     * @return void
     * @throws RequestInValidException if $haystack doesn't contain 'objecttype' or the values are not 'image' or 'video'
     */
    public static function containsObjectTypeImageOrVideo(array $haystack, string $key = "objecttype")
    {
        self::isOneOf($haystack, $key, ['image', 'video']);
    }


    /**
     * Tests if $haystack contains a key named 'source' with the values in self::SOURCES
     *
     * @param array $haystack will be tested for the key and values
     *
     * @return void
     * @throws RequestInValidException if $haystack doesn't contain 'source' or the values are not in the source array
     */
    public static function containsSource(array $haystack)
    {
        self::isOneOf($haystack, 'source', self::SOURCES);
    }


    /**
     * Tests if $haystack contains a key named 'content'
     *
     * @param array|null $haystack will be tested for 'content'
     *
     * @return void
     * @throws RequestInValidException when $haystack is null or doesn't contain content
     */
    public static function containsKeyContent(?array $haystack)
    {
        if (isset($haystack) === false) {
            throw new RequestInValidException("Missing json body");
        }
        self::containsKeys($haystack, 'body', ['content']);
    }


    /**
     * Tests if $key is in $haystack and values of that $key are one of the $possibleValues.
     *
     * @param array    $haystack       the array that will be tested for key and values
     * @param string   $key            is in $haystack and
     * @param string[] $possibleValues array of possible values for the $key
     *
     * @return void
     * @throws RequestInValidException when $key is not in $haystack or value is not one of $possibleValues
     */
    public static function isOneOf(array $haystack, string $key, array $possibleValues)
    {
        self::containsKeys($haystack, 'content', [$key]);

        if (in_array($haystack[$key], $possibleValues) === false) {
            $s = implode(", ", $possibleValues);
            throw new RequestInValidException("$key must be one of [$s]");
        }
    }


    /**
     * Tests if $haystack contains key 'limit' and 'offset' with numeric values
     * and if key $sortOrderName contains values 'asc' or 'desc'
     *
     * @param array  $haystack      the array that will be tested
     * @param string $sortOrderName the key should that contains values 'asc' or 'desc', default='sortOrder'
     *
     * @return void
     * @throws RequestInValidException if $haystack doesn't contain numeric values 'limit' and 'offset'
     *                                 and $sortOrderName with values 'asc' or 'desc'
     */
    public static function containsSortOrderLimitOffset(array $haystack, string $sortOrderName = 'sortorder')
    {
        ActionRequestValidation::containsPositiveIntegerValue($haystack, 'limit');
        ActionRequestValidation::containsPositiveIntegerValue($haystack, 'offset');
        self::isOneOf($haystack, $sortOrderName, ['asc', 'desc']);
    }


    /**
     * Tests if $haystack contains key 'objecttype' with values either 'media' or 'folder'
     *
     * @param array $haystack that will be tested for key and values
     *
     * @return void
     * @throws RequestInValidException
     */
    public static function containsObjectTypeMediaOrFolder(array $haystack)
    {
        self::isOneOf($haystack, 'objecttype', ['media', 'folder']);
    }


    /**
     * Tests if $value string is empty or too large
     *
     * @param string $value string that will be tested
     *
     * @return void
     * @throws RequestInValidException when string is empty or larger than 250 chars
     */
    public static function stringSize(string $value)
    {
        if (strlen($value) < 1) {
            throw new RequestInValidException("String must contain content");
        }
        if (strlen($value) > 250) {
            throw new RequestInValidException("String can't contain more than 250 chars");
        }
    }


}
