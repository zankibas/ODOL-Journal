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
 * COUNTER report instance class
 */
class Report extends ReportBuilder
{
    /**
     * @var string Report attribute "Created"
     */
    private $created;
    /**
     * @var string Report attribute "ID"
     */
    private $id;
    /**
     * @var string Report attribute "Version"
     */
    private $version;
    /**
     * @var string Report attribute "Name"
     */
    private $name;
    /**
     * @var string Report attribute "Title"
     */
    private $title;
    /**
     * @var Vendor
     */
    private $vendor;
    /**
     * @var Customer[] one or more COUNTER\Customer objects
     */
    private $customer;

    /**
     * Construct the object
     *
     * @param string $id
     * @param string $version
     * @param string $name
     * @param string $title
     * @param Customer $customers COUNTER\Customer
     * @param Vendor $vendor COUNTER\Vendor
     * @param string $created optional
     *
     * @throws \Exception
     */
    public function __construct($id, $version, $name, $title, $customers, $vendor, $created = '')
    {
        foreach (['id', 'version', 'name', 'title', 'created'] as $arg) {
            $this->$arg = $this->validateString($$arg);
        }
        if (!$created) {
            $this->created = date("Y-m-d\Th:i:sP");
        }
        $this->vendor = $this->validateOneOf($vendor, 'Vendor');
        $this->customer = $this->validateOneOrMoreOf($customers, 'Customer');
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
            if (isset($array['ID']) && isset($array['Version']) && isset($array['Name']) && isset($array['Title']) && isset($array['Customer']) && isset($array['Vendor'])) {
                // Nicely structured associative array
                $customers = parent::buildMultiple('COUNTER\Customer', $array['Customer']);
                return new self(
                    $array['ID'],
                    $array['Version'],
                    $array['Name'],
                    $array['Title'],
                    $customers,
                    Vendor::build($array['Vendor']),
                    $array['Created'] ?? ''
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
        $root = $doc->appendChild($doc->createElement('Report'));
        foreach (['Created', 'ID', 'Version', 'Name', 'Title'] as $arg) {
            $lcarg = strtolower($arg);
            $attrAttr = $doc->createAttribute($arg);
            $attrAttr->appendChild($doc->createTextNode($this->$lcarg));
            $root->appendChild($attrAttr);
        }
        $root->appendChild($doc->importNode($this->vendor->asDOMDocument()->documentElement, true));
        foreach ($this->customer as $customer) {
            $root->appendChild($doc->importNode($customer->asDOMDocument()->documentElement, true));
        }
        return $doc;
    }
}
