<?php

/**

 * @package SimplePie
 * @copyright 2004-2016 Ryan Parman, Sam Sneddon, Ryan McCue
 * @author Ryan Parman
 * @author Sam Sneddon
 * @author Ryan McCue
 * @link http://simplepie.org/ SimplePie
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 */

namespace SimplePie;

/**
 * Manages all author-related data
 *
 * Used by {@see Item::get_author()} and {@see SimplePie::get_authors()}
 *
 * This class can be overloaded with {@see SimplePie::set_author_class()}
 *
 * @package SimplePie
 * @subpackage API
 */
class Author
{
    /**
     * Author's name
     *
     * @var string
     * @see get_name()
     */
    public $name;

    /**
     * Author's link
     *
     * @var string
     * @see get_link()
     */
    public $link;

    /**
     * Author's email address
     *
     * @var string
     * @see get_email()
     */
    public $email;

    /**
     * Constructor, used to input the data
     *
     * @param string $name
     * @param string $link
     * @param string $email
     */
    public function __construct($name = null, $link = null, $email = null)
    {
        $this->name = $name;
        $this->link = $link;
        $this->email = $email;
    }

    /**
     * String-ified version
     *
     * @return string
     */
    public function __toString()
    {
        // There is no $this->data here
        return md5(serialize($this));
    }

    /**
     * Author's name
     *
     * @return string|null
     */
    public function get_name()
    {
        if ($this->name !== null) {
            return $this->name;
        }

        return null;
    }

    /**
     * Author's link
     *
     * @return string|null
     */
    public function get_link()
    {
        if ($this->link !== null) {
            return $this->link;
        }

        return null;
    }

    /**
     * Author's email address
     *
     * @return string|null
     */
    public function get_email()
    {
        if ($this->email !== null) {
            return $this->email;
        }

        return null;
    }
}

class_alias('SimplePie\Author', 'SimplePie_Author');
