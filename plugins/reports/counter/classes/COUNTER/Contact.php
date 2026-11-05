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
 * COUNTER contact class
 */
class Contact extends ReportBuilder
{
    /**
     * @var string Contact element "Contact"
     */
    private $contact;
    /**
     * @var string Contact element "Email"
     */
    private $email;

    /**
     * Construct the object
     *
     * @param string $contact optional
     * @param string $email optional
     *
     * @throws \Exception
     */
    public function __construct($contact = '', $email = '')
    {
        foreach (['contact', 'email'] as $arg) {
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
            if (isset($array['E-mail'])) {
                $array['Email'] = $array['E-mail'];
                unset($array['E-mail']);
            }
            if (isset($array['Contact']) || isset($array['Email'])) {
                return new self($array['Contact'] ? $array['Contact'] : '', $array['Email'] ? $array['Email'] : '');
            }
            if (count(array_keys($array)) == 1 && parent::isAssociative($array)) {
                // Loosely structured associative array (name/email => name/email)
                foreach ($array as $k => $v) {
                    if (filter_var($k, FILTER_VALIDATE_EMAIL)) {
                        // email => name
                        return new self($v, $k);
                    }
                    // name => email
                    return new self($k, $v);
                }
            } elseif (count(array_keys($array)) == 1 && !parent::isAssociative($array)) {
                // Loosely array with a name or email
                if (filter_var($array[0], FILTER_VALIDATE_EMAIL)) {
                    // email
                    return new self('', $array[0]);
                }
                // name
                return new self($array[0]);
            }
        } elseif (is_string($array)) {
            // Just a name or email
            if (filter_var($array, FILTER_VALIDATE_EMAIL)) {
                // email
                return new self('', $array);
            }
            // name
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
        $root = $doc->appendChild($doc->createElement('Contact'));
        if ($this->contact) {
            $root->appendChild($doc->createElement('Contact'))->appendChild($doc->createTextNode($this->contact));
        }
        if ($this->email) {
            $root->appendChild($doc->createElement('E-mail'))->appendChild($doc->createTextNode($this->email));
        }
        return $doc;
    }
}
