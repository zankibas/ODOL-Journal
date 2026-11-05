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
 * COUNTER reports base class
 *
 */
class Reports extends ReportBuilder
{
    /**
     * @var Report[] one or more COUNTER\Report objects
     */
    private $report = [];

    /**
     * Construct the object
     *
     * @param Report[] $reports COUNTER\Report array
     *
     * @throws \Exception
     */
    public function __construct($reports)
    {
        $this->report = $this->validateOneOrMoreOf($reports, 'Report');
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
            if (isset($array['Report'])) {
                // Nicely structured associative array
                $reports = parent::buildMultiple('COUNTER\Report', $array['Report']);
                return new self($reports);
            }
            if (!parent::isAssociative($array)) {
                // Just an array of reports
                $reports = parent::buildMultiple('COUNTER\Report', $array);
                return new self($reports);
            }
        }
        parent::build($array);
    }

    /**
     * Add a report
     *
     * @param Report $report CounterReport
     *
     * @throws \Exception
     */
    public function addReport($report)
    {
        $this->report[] = $this->validateOneOf($report, 'Report');
    }

    /**
     * Get an array of reports
     *
     * @return Report[] COUNTER\Report array
     */
    public function getReports()
    {
        return $this->report;
    }

    /**
     * Output this object as a DOMDocument
     *
     * @return \DOMDocument
     */
    public function asDOMDocument()
    {
        $doc = new \DOMDocument('1.0', 'utf-8');
        $root = $doc->appendChild($doc->createElementNS(self::COUNTER_NAMESPACE, 'Reports'));
        $xmlns = $doc->createAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:schemaLocation');
        $xmlns->value = self::COUNTER_NAMESPACE . ' http://www.niso.org/schemas/sushi/counter4_1.xsd';
        $root->appendChild($xmlns);
        foreach ($this->report as $rep) {
            $root->appendChild($doc->importNode($rep->asDOMDocument()->documentElement, true));
        }
        return $doc;
    }
}
