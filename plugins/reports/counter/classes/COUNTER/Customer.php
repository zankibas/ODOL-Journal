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
 * COUNTER customer class
 */
class Customer extends ReportBuilder
{
    /**
     * @var string Customer element "Name"
     */
    private $name;
    /**
     * @var string Customer element "ID"
     */
    private $id;
    /**
     * @var Contact[] zero or more COUNTER\Contact elements
     */
    private $contact;
    /**
     * @var string Customer element "WebSiteUrl"
     */
    private $webSiteUrl;
    /**
     * @var string Customer element "LogoUrl"
     */
    private $logoUrl;
    /**
     * @var Consortium Customer element "Consortium"
     */
    private $consortium;
    /**
     * @var Identifier[] zero or more COUNTER\Identifier elements
     */
    private $institutionalIdentifier;
    /**
     * @var ReportItems[] one or more COUNTER\ReportItems elements
     */
    private $reportItems;

    /**
     * Construct the object
     *
     * @param string $id
     * @param ReportItems[] $reportItems COUNTER\ReportItems array
     * @param string $name optional
     * @param Contacts[] $contacts optional COUNTER\Contacts array
     * @param string $webSiteUrl optional
     * @param string $logoUrl optional
     * @param Consortium $consortium optional COUNTER\Consortium
     * @param Identifier[] $institutionalIdentifier optional COUNTER\Identifier array
     *
     * @throws \Exception
     */
    public function __construct($id, $reportItems, $name = '', $contacts = [], $webSiteUrl = '', $logoUrl = '', $consortium = null, $institutionalIdentifier = [])
    {
        foreach (['id', 'name', 'webSiteUrl', 'logoUrl'] as $arg) {
            $this->$arg = $this->validateString($$arg);
        }
        $this->reportItems = $this->validateOneOrMoreOf($reportItems, 'ReportItems');
        $this->contact = $this->validateZeroOrMoreOf($contacts, 'Contact');
        $this->consortium = $this->validateZeroOrOneOf($consortium, 'Consortium');
        $this->institutionalIdentifier = $this->validateZeroOrMoreOf($institutionalIdentifier, 'Identifier');
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
            if (isset($array['ID']) && isset($array['ReportItems'])) {
                // Nicely structured associative array
                $items = parent::buildMultiple('COUNTER\ReportItems', $array['ReportItems']);
                $ids = parent::buildMultiple('COUNTER\Identifier', $array['InstitutionalIdentifier'] ?? []);
                $contacts = parent::buildMultiple('COUNTER\Contact', $array['Contact'] ?? []);
                return new self(
                    $array['ID'],
                    $items,
                    $array['Name'] ?? '',
                    $contacts,
                    $array['WebSiteUrl'] ?? '',
                    $array['LogoUrl'] ?? '',
                    isset($array['Consortium']) ? Consortium::build($array['Consortium']) : null,
                    $ids
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
        $root = $doc->appendChild($doc->createElement('Customer'));
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
        if ($this->consortium) {
            $root->appendChild($doc->importNode($this->consortium->asDOMDocument()->documentElement, true));
        }
        if ($this->institutionalIdentifier) {
            foreach ($this->institutionalIdentifier as $id) {
                $root->appendChild($doc->importNode($id->asDOMDocument()->documentElement, true));
            }
        }
        foreach ($this->reportItems as $rep) {
            $root->appendChild($doc->importNode($rep->asDOMDocument()->documentElement, true));
        }
        return $doc;
    }
}
