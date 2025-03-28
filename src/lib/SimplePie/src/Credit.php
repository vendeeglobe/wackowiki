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
 * Handles `<media:credit>` as defined in Media RSS
 *
 * Used by {@see \SimplePie\Enclosure::get_credit()} and {@see \SimplePie\Enclosure::get_credits()}
 *
 * This class can be overloaded with {@see \SimplePie\SimplePie::set_credit_class()}
 *
 * @package SimplePie
 * @subpackage API
 */
class Credit
{
    /**
     * Credited role
     *
     * @var string
     * @see get_role()
     */
    public $role;

    /**
     * Organizational scheme
     *
     * @var string
     * @see get_scheme()
     */
    public $scheme;

    /**
     * Credited name
     *
     * @var string
     * @see get_name()
     */
    public $name;

    /**
     * Constructor, used to input the data
     *
     * For documentation on all the parameters, see the corresponding
     * properties and their accessors
     */
    public function __construct($role = null, $scheme = null, $name = null)
    {
        $this->role = $role;
        $this->scheme = $scheme;
        $this->name = $name;
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
     * Get the role of the person receiving credit
     *
     * @return string|null
     */
    public function get_role()
    {
        if ($this->role !== null) {
            return $this->role;
        }

        return null;
    }

    /**
     * Get the organizational scheme
     *
     * @return string|null
     */
    public function get_scheme()
    {
        if ($this->scheme !== null) {
            return $this->scheme;
        }

        return null;
    }

    /**
     * Get the credited person/entity's name
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
}

class_alias('SimplePie\Credit', 'SimplePie_Credit');
