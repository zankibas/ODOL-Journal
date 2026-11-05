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
 * COUNTER report builder class
 * Other classes in this package extend from this core class to have access to several generic functions
 *
 * @todo should "tool-like" functions be moved to static calls from an non-inherited Tools class?
 */
class ReportBuilder
{
    public const COUNTER_NAMESPACE = 'http://www.niso.org/schemas/counter';

    /**
     * Validate that $object is a $className instance.  If valid, return the object, otherwise, throw an exception
     *
     * @param object $object
     * @param string $className If no namespace specified, defaults to the COUNTER namespace
     *
     * @throws \Exception
     *
     * @return object
     */
    protected function validateOneOf($object, $className)
    {
        if (strpos($className, '\\') === false) {
            $expectedClassname = 'COUNTER\\' . $className;
        } elseif (strpos($className, '\\') === 0) {
            $expectedClassname = substr($className, 1);
        } else {
            $expectedClassname = $className;
        }
        if (is_null($object)) {
            throw new \Exception('Invalid object. Expected "' . $expectedClassname . '", got "NULL"');
        }
        if (is_array($object)) {
            throw new \Exception('Invalid class. Expected "' . $expectedClassname . '", got unparsable array');
        }
        if (is_string($object)) {
            switch ($className) {
                case '\DateTime':
                    $date = date_create($object);
                    if ($date) {
                        return $date;
                    }
                    break;
                default:
            }
            throw new \Exception('Invalid class. Expected "' . $expectedClassname . '", got unparsable string');
        }
        if ($expectedClassname == get_class($object) || is_subclass_of($object, $expectedClassname)) {
            return $object;
        }
        throw new \Exception('Invalid class. Expected "' . $expectedClassname . '", got "' . get_class($object) . '"');
    }

    /**
     * Validate that $objects is an array of $className instances.  If valid, return the array, otherwise, throw an exception
     *
     * @param object|array $objects
     * @param string $className
     *
     * @throws \Exception
     *
     * @return array
     */
    protected function validateOneOrMoreOf($objects, $className)
    {
        if (is_array($objects)) {
            foreach ($objects as $object) {
                $this->validateOneOf($object, $className);
            }
            return $objects;
        }
        return [$this->validateOneOf($objects, $className)];
    }

    /**
     * Validate that $objects is an array of $className instances, or is empty.  If valid, return the array or empty, otherwise, throw an exception
     *
     * @param object|array $objects
     * @param string $className
     *
     * @throws \Exception
     *
     * @return array
     */
    protected function validateZeroOrMoreOf($objects, $className)
    {
        if (empty($objects)) {
            return;
        }
        return $this->validateOneOrMoreOf($objects, $className);
    }

    /**
     * Validate that $object is a $className instance, or is empty.  If valid, return the object or empty, otherwise, throw an exception
     *
     * @param object $object
     * @param string $className
     *
     * @throws \Exception
     *
     * @return object
     */
    protected function validateZeroOrOneOf($object, $className)
    {
        if (empty($object)) {
            return;
        }
        return $this->validateOneOf($object, $className);
    }

    /**
     * Validate that $yr is an integer.  If valid, return the year, otherwise, throw an exception
     *
     * @param int $int
     *
     * @throws \Exception
     *
     * @return int
     */
    protected function validatePositiveInteger($int)
    {
        $intval = intval($int);
        if (!is_int($intval) || $intval < 0) {
            throw new \Exception('Invalid positive integer: ' . gettype($int) . ' value ' . $int);
        }
        return $intval;
    }

    /**
     * Validate that $string is string.  If valid, return the string, otherwise, throw an exception
     *
     * @param string $string
     *
     * @throws \Exception
     *
     * @return string
     */
    protected function validateString($string)
    {
        if (!is_string($string)) {
            throw new \Exception('Invalid string: ' . gettype($string));
        }
        return $string;
    }

    /**
     * Validate that $array is an array of strings.  If valid, return the array, otherwise, throw an exception
     *
     * @param array $array
     *
     * @throws \Exception
     *
     * @return array
     */
    protected function validateStrings($array)
    {
        if (is_array($array)) {
            foreach ($array as $string) {
                $this->validateString($string);
            }
            return $array;
        }
        if (is_string($array)) {
            return [$array];
        }
        if (!empty($array)) {
            throw new \Exception('Invalid string array: ' . gettype($array));
        }
    }

    /**
     * Check an array to see if it has associative (non-numeric) keys
     *
     * @param array $array
     *
     * @return bool
     */
    protected static function isAssociative($array)
    {
        return count(array_filter(array_keys($array), 'is_string')) > 0;
    }

    /**
     * Given an classname and an array, call the $classname::build method and return the built object(s)
     * The array can be associative (for a single object), or an array of associative arrays (for multiple objects)
     *
     * @param string $classname
     * @param array $array
     *
     * @return mixed object or array of objects
     */
    protected static function buildMultiple($classname, $array)
    {
        if (!is_array($array)) {
            return [];
        }
        if (self::isAssociative($array)) {
            return $classname::build($array);
        }
        $elements = [];
        foreach ($array as $element) {
            $elements[] = $classname::build($element);
        }
        return $elements;
    }

    /**
     * Return an array of valid Item Data Types
     *
     * @return array
     *
     * @todo verify addition of "Article" here for proposed release 4.2.
     */
    protected function getItemDataTypes()
    {
        return ['Journal', 'Database', 'Platform', 'Book', 'Collection', 'Multimedia', 'Article'];
    }

    /**
     * Return an array of valid Identifier Types
     *
     * @return array
     */
    protected function getIdentifierTypes()
    {
        return ['Online_ISSN', 'Print_ISSN', 'Online_ISBN', 'Print_ISBN', 'DOI', 'Proprietary'];
    }

    /**
     * Return an array of valid Contributor Identifier Types
     *
     * @return array
     */
    protected function getContributorIdentifierTypes()
    {
        return ['ORCID', 'ISNI', 'Proprietary'];
    }

    /**
     * Return an array of valid Date Types
     *
     * @return array
     *
     * @todo these values are preliminary; need an update from COUNTER
     */
    protected function getDateTypes()
    {
        return ['PubDate', 'FirstAccessedOnline', 'Proprietary'];
    }

    /**
     * Return an array of valid Date Types
     *
     * @return array
     *
     * @todo these values are preliminary; need an update from COUNTER
     */
    protected function getAttributeTypes()
    {
        return ['ArticleVersion', 'ArticleType', 'QualificationName', 'QualificationLevel'];
    }

    /**
     * Return an array of valid Metric Types
     *
     * @return array
     */
    protected function getMetricTypes()
    {
        return ['abstract', 'audio', 'data_set', 'ft_epub', 'ft_html', 'ft_html_mobile', 'ft_pdf', 'ft_pdf_mobile', 'ft_ps', 'ft_ps_mobile', 'ft_total', 'image', 'multimedia', 'no_license', 'other', 'podcast', 'record_view', 'reference', 'result_click', 'search_fed', 'search_reg', 'sectioned_html', 'toc', 'turnaway', 'video'];
    }

    /**
     * Return an array of valid Categories
     *
     * @return array
     */
    protected function getCategories()
    {
        return ['Requests', 'Searches', 'Access_denied'];
    }

    /**
     * Output this object as XML
     * Inherited by all children of this object; all children must implement asDOMDocument().
     *
     * @return string
     */
    public function __toString()
    {
        $doc = $this->asDOMDocument();
        $doc->formatOutput = true;
        return $doc->saveXML();
    }

    /**
     * Do NOT Output this object as a DOMDocument
     * This method must be implemented in the subclass
     *
     * @throws \Exception
     */
    public function asDOMDocument()
    {
        throw new \Exception(get_class($this) . ' does not implement asDOMDocument()');
    }

    /**
     * Do NOT build this object
     * This method must be implemented in the subclass
     * Subclasses should call this method if unable to build the object in order to report an error.
     *
     * @throws \Exception
     *
     * @return never
     */
    public static function build($array)
    {
        throw new \Exception('Failed to build ' . static::class . ' from data ' . var_export($array, true));
    }
}
