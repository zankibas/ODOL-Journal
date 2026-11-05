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
 * COUNTER item date class
 */
class ItemDate extends ReportBuilder
{
    /**
     * @var string ItemDate element "Type"
     */
    private $type;
    /**
     * @var \DateTime ItemDate element "Value"
     */
    private $value;

    /**
     * Construct the object
     *
     * @param string $type
     * @param \DateTime $value
     *
     * @throws \Exception
     */
    public function __construct($type, $value)
    {
        $this->type = $this->validateString($type);
        if (!in_array($type, $this->getDateTypes())) {
            throw new \Exception('Invalid type: ' . $type);
        }
        $this->value = $this->validateOneOf($value, '\DateTime');
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
            if (isset($array['Type']) && isset($array['Value'])) {
                // Nicely structured associative array
                return new self($array['Type'], $array['Value']);
            }
            if (count(array_keys($array)) == 1 && parent::isAssociative($array)) {
                // Loosely structured associative array (type => value)
                foreach ($array as $k => $v) {
                    return new self($k, $v);
                }
            }
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
        $root = $doc->appendChild($doc->createElement('ItemDate'));
        $root->appendChild($doc->createElement('Type'))->appendChild($doc->createTextNode($this->type));
        $root->appendChild($doc->createElement('Value'))->appendChild($doc->createTextNode(date_format($this->value, 'Y-m-d')));
        return $doc;
    }
}
