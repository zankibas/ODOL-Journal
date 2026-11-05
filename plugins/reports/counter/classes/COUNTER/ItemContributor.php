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
 * COUNTER item contributor class
 */
class ItemContributor extends ReportBuilder
{
    /**
     * @var ItemContributorId[] zero or more COUNTER\ItemContributorId elements
     */
    private $itemContributorId;
    /**
     * @var string ItemContributor element "Name"
     */
    private $itemContributorName;
    /**
     * @var string[] ItemContributor element "Affiliation"
     */
    private $itemContributorAffiliation;
    /**
     * @var string[] ItemContributor element "Role"
     */
    private $itemContributorRole;

    /**
     * Construct the object
     *
     * @param ContributorId[] $itemContributorIds optional COUNTER\ContributorId array
     * @param string $itemContributorName optional
     * @param string[] $itemContributorAffiliations optional string array
     * @param string[] $itemContributorRoles optional string array
     *
     * @throws \Exception
     */
    public function __construct($itemContributorIds = [], $itemContributorName = '', $itemContributorAffiliations = [], $itemContributorRoles = [])
    {
        $this->itemContributorId = $this->validateZeroOrMoreOf($itemContributorIds, 'ItemContributorId');
        $this->itemContributorName = $this->validateString($itemContributorName);
        $this->itemContributorAffiliation = $this->validateStrings($itemContributorAffiliations);
        $this->itemContributorRole = $this->validateStrings($itemContributorRoles);
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
            if (isset($array['ItemContributorID']) || isset($array['ItemContributorName']) || isset($array['ItemContributorAffiliation']) || isset($array['ItemContributorRole'])) {
                // Nicely structured associative array
                $ids = parent::buildMultiple('COUNTER\ItemContributorId', $array['ItemContributorID'] ?? []);
                return new self(
                    $ids,
                    $array['ItemContributorName'] ?? '',
                    $array['ItemContributorAffiliation'] ?? '',
                    $array['ItemContributorRole'] ?? ''
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
        $root = $doc->appendChild($doc->createElement('ItemContributor'));
        if ($this->itemContributorId) {
            foreach ($this->itemContributorId as $id) {
                $root->appendChild($doc->importNode($id->asDOMDocument()->documentElement, true));
            }
        }
        if ($this->itemContributorName) {
            $root->appendChild($doc->createElement('ItemContributorName'))->appendChild($doc->createTextNode($this->itemContributorName));
        }
        if ($this->itemContributorAffiliation) {
            foreach ($this->itemContributorAffiliation as $affiliation) {
                $root->appendChild($doc->createElement('ItemContributorAffiliation', $affiliation));
            }
        }
        if ($this->itemContributorRole) {
            foreach ($this->itemContributorRole as $role) {
                $root->appendChild($doc->createElement('ItemContributorRole', $role));
            }
        }
        return $doc;
    }
}
