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
 * COUNTER performance counter class
 */
class Metric extends ReportBuilder
{
    /**
     * @var int Metric element "PubYr"
     */
    private $pubYr;
    /**
     * @var int Metric element "PubYrFrom"
     */
    private $pubYrFrom;
    /**
     * @var int Metric element "PubYrTo"
     */
    private $pubYrTo;
    /**
     * @var DateRange Metric element "Period"
     */
    private $period;
    /**
     * @var string Metric element "Category"
     */
    private $category;
    /**
     * @var PerformanceCounter[] one or more COUNTER\PerformanceCounter elements
     */
    private $instance;

    /**
     * Construct the object
     *
     * @param DateRange $period COUNTER\DateRange array
     * @param string $category COUNTER\Category array
     * @param array $instances COUNTER\PerformanceCounter array
     * @param int $pubYrFrom optional
     * @param int $pubYrTo optional
     * @param int $pubYr optional
     * @param null|mixed $pubYrFrom
     * @param null|mixed $pubYrTo
     * @param null|mixed $pubYr
     *
     * @throws \Exception
     */
    public function __construct($period, $category, $instances, $pubYrFrom = null, $pubYrTo = null, $pubYr = null)
    {
        $this->period = $this->validateOneOf($period, 'DateRange');
        $this->category = $this->validateString($category);
        if (!in_array($category, $this->getCategories())) {
            throw new \Exception('Invalid category: ' . $category);
        }
        $this->instance = $this->validateOneOrMoreOf($instances, 'PerformanceCounter');
        if ($pubYrFrom) {
            $this->pubYrFrom = $this->validatePositiveInteger($pubYrFrom);
        }
        if ($pubYrTo) {
            $this->pubYrTo = $this->validatePositiveInteger($pubYrTo);
        }
        if ($pubYr) {
            $this->pubYr = $this->validatePositiveInteger($pubYr);
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
            if (isset($array['Period']) && isset($array['Instance']) && isset($array['Category'])) {
                // Nicely structured associative array
                $instances = parent::buildMultiple('COUNTER\PerformanceCounter', $array['Instance']);
                return new self(
                    DateRange::build($array['Period']),
                    $array['Category'],
                    $instances,
                    $array['PubYrFrom'] ?? null,
                    $array['PubYrTo'] ?? null,
                    $array['PubYr'] ?? null
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
        $root = $doc->appendChild($doc->createElement('ItemPerformance'));
        if ($this->period) {
            $root->appendChild($doc->importNode($this->period->asDOMDocument()->documentElement, true));
        }
        if ($this->category) {
            $root->appendChild($doc->createElement('Category'))->appendChild($doc->createTextNode($this->category));
        }
        foreach ($this->instance as $instance) {
            $root->appendChild($doc->importNode($instance->asDOMDocument()->documentElement, true));
        }
        foreach (['pubYr', 'pubYrFrom', 'pubYrTo'] as $pubYrKey) {
            if ($this->$pubYrKey) {
                $root->appendChild($doc->createElement(ucfirst($pubYrKey)))->appendChild($doc->createTextNode($this->$pubYrKey));
                $attr = $doc->createAttribute(ucfirst($pubYrKey));
                $attr->value = $this->$pubYrKey;
                $root->appendChild($attr);
            }
        }
        return $doc;
    }
}
