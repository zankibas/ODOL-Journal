<?php

/**
 * Copyright (c) 2015 University of Pittsburgh
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

/**
 * COUNTER Reports classes, release 4.1
 * Represents the COUNTER XSD schema in class form
 *
 * @link http://www.niso.org/schemas/sushi
 *
 * @author Clinton Graham, University of Pittsburgh Library System, University of Pittsburgh <ctgraham@pitt.edu> +1-412-383-1057
 * @copyright 2015 University of Pittsburgh
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPL 2.0 or later
 *
 * @package COUNTER
 *
 * @version 0.3
 */

namespace COUNTER;

/**
 * COUNTER consortium class
 */
class Consortium extends ReportBuilder
{
    /**
     * @var string Consortium element "Code"
     */
    private $code;
    /**
     * @var string Consortium element "WellKnownName"
     */
    private $wellKnownName;

    /**
     * Construct the object
     *
     * @param string $wellKnownName
     * @param string $code optional
     */
    public function __construct($wellKnownName, $code = '')
    {
        foreach (['wellKnownName', 'code'] as $arg) {
            $this->$arg = $this->validateString($$arg);
        }
    }

    /**
     * Construct the object from an array
     *
     * @param array $array Hash of key-values
     *
     * @throws \Exception
     *
     * @return self
     */
    public static function build($array)
    {
        if (is_array($array)) {
            if (isset($array['WellKnownName'])) {
                // Nicely structured associative array
                return new self($array['WellKnownName'], $array['Code'] ? $array['Code'] : '');
            }
            if (count(array_keys($array)) == 1 && parent::isAssociative($array)) {
                // Loosely structured associative array (name => code)
                foreach ($array as $k => $v) {
                    return new self($k, $v);
                }
            } elseif (count(array_keys($array)) == 1 && !parent::isAssociative($array)) {
                // Loosely array with a name
                return new self($array[0]);
            }
        } elseif (is_string($array)) {
            // Just a name
            return new self($array);
        }
        parent::build($array);
    }

    /**
     * Output this object as a DOMDocument
     *
     * @return \DOMDocument
     */
    public function asDOMDocument()
    {
        $doc = new \DOMDocument('1.0', 'utf-8');
        $root = $doc->appendChild($doc->createElement('Consortium'));
        if ($this->code) {
            $root->appendChild($doc->createElement('Code'))->appendChild($doc->createTextNode($this->code));
        }
        $root->appendChild($doc->createElement('WellKnownName'))->appendChild($doc->createTextNode($this->wellKnownName));
        return $doc;
    }
}
