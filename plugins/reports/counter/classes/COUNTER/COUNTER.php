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
 *
 * @section DESCRIPTION
 *
 * This is a set of classes to represent the Project COUNTER schema ( http://www.projectcounter.org/ )
 * It is basically an encapsulation of DOMDocument, with type checking.
 * Construct any object, then cast it as a string to retrieve the XML, or call asDOMDocument() to retrieve the DOM.
 * $report = new COUNTER\Report(
 * 	'reportId',
 * 	'reportVersion',
 * 	'reportName',
 * 	'reportTitle',
 * 	new COUNTER\Customer(
 * 		'customerId',
 * 		new COUNTER\ReportItems(
 * 			'itemPlatform',
 * 			'itemName',
 * 			'Journal',
 * 			array(
 * 				new COUNTER\Metric(
 * 					new COUNTER\DateRange(date_create("first day of previous month"), date_create("last day of previous month")),
 * 					'Requests',
 * 					array(new COUNTER\PerformanceCounter('ft_html', 128), new COUNTER\PerformanceCounter('ft_pdf', 129))
 * 				),
 * 				new COUNTER\Metric(
 * 					new COUNTER\DateRange(date_create("first day of this month"), date_create("last day of this month")),
 * 					'Requests',
 * 					new COUNTER\PerformanceCounter('other', 121)
 * 				)
 * 			)
 * 		)
 * 	),
 * 	new COUNTER\Vendor('vendorId')
 * );
 * echo $report;
 */

namespace COUNTER;

spl_autoload_register(function (string $className) {
    if (
        substr($className, 0, strlen(__NAMESPACE__)) === __NAMESPACE__
        && file_exists($classPath = __DIR__ . str_replace('\\' ,'/', substr($className, strlen(__NAMESPACE__))) . '.php')
    ) {
        require_once $classPath;
    }
});
