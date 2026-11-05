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
 * COUNTER vendor class
 */
class Vendor extends ReportBuilder
{
    /**
     * @var string Vendor element "Name"
     */
    private $name;
    /**
     * @var string Vendor element "ID"
     */
    private $id;
    /**
     * @var Contact[] zero or more COUNTER\Contact elements
     */
    private $contact = [];
    /**
     * @var string Vendor element "WebSiteUrl"
     */
    private $webSiteUrl;
    /**
     * @var string Vendor element "LogoUrl"
     */
    private $logoUrl;

    /**
     * Construct the object
     *
     * @param string $id
     * @param string $name optional
     * @param Contact[] $contacts optional COUNTER\Contact array
     * @param string $webSiteUrl optional
     * @param string $logoUrl optional
     *
     * @throws \Exception
     */
    public function __construct($id, $name = '', $contacts = [], $webSiteUrl = '', $logoUrl = '')
    {
        foreach (['id', 'name', 'webSiteUrl', 'logoUrl'] as $arg) {
            $this->$arg = $this->validateString($$arg);
        }
        $this->contact = $this->validateZeroOrMoreOf($contacts, 'Contact');
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
            if (isset($array['ID'])) {
                // Nicely structured associative array
                $contacts = parent::buildMultiple('COUNTER\Contact', $array['Contact'] ?? []);
                return new self(
                    $array['ID'],
                    $array['Name'] ?? '',
                    $contacts,
                    $array['WebSiteUrl'] ?? '',
                    $array['LogoUrl'] ?? ''
                );
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
        $root = $doc->appendChild($doc->createElement('Vendor'));
        if ($this->name) {
            $root->appendChild($doc->createElement('Name'))->appendChild($doc->createTextNode($this->name));
        }
        $root->appendChild($doc->createElement('ID'))->appendChild($doc->createTextNode($this->id));
        if ($this->contact) {
            foreach ($this->contact as $contact) {
                $root->appendChild($doc->importNode($contact->asDOMDocument()->documentElement, true));
            }
        }
        if ($this->webSiteUrl) {
            $root->appendChild($doc->createElement('WebSiteUrl'))->appendChild($doc->createTextNode($this->webSiteUrl));
        }
        if ($this->logoUrl) {
            $root->appendChild($doc->createElement('LogoUrl'))->appendChild($doc->createTextNode($this->logoUrl));
        }
        return $doc;
    }
}
