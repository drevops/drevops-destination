<?php /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */

/**
 * This file is part of the Peast package
 *
 * (c) Marco Marchiò <marco.mm89@gmail.com>
 *
 * For the full copyright and license information refer to the LICENSE file
 * distributed with this source code
 */
namespace Peast\Syntax\Node;

use Peast\Syntax\SourceLocation;
use Peast\Syntax\Position;

/**
 * Base class for all the nodes generated by Peast.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 * 
 * @abstract
 */
abstract class Node implements \JSONSerializable
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "type" => false,
        "location" => false,
        "leadingComments" => false,
        "trailingComments" => false
    );
    
    /**
     * Node location in the source code
     * 
     * @var SourceLocation
     */
    public $location;
    
    /**
     * Leading comments array
     *
     * @var Comment[]
     */
    protected $leadingComments = array();

    /**
     * Trailing comments array
     *
     * @var Comment[]
     */
    protected $trailingComments = array();

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->location = new SourceLocation;
    }
    
    /**
     * Returns node type
     * 
     * @return string
     */
    public function getType()
    {
        $class = explode("\\", get_class($this));
        return array_pop($class);
    }
    
    /**
     * Sets leading comments array
     *
     * @param Comment[] $comments Comments array
     *
     * @return $this
     */
    public function setLeadingComments($comments)
    {
        $this->assertArrayOf($comments, "Comment");
        $this->leadingComments = $comments;
        return $this;
    }

    /**
     * Returns leading comments array
     *
     * @return Comment[]
     */
    public function getLeadingComments()
    {
        return $this->leadingComments;
    }

    /**
     * Sets trailing comments array
     *
     * @param Comment[] $comments Comments array
     *
     * @return $this
     */
    public function setTrailingComments($comments)
    {
        $this->assertArrayOf($comments, "Comment");
        $this->trailingComments = $comments;
        return $this;
    }

    /**
     * Returns trailing comments array
     *
     * @return Comment[]
     */
    public function getTrailingComments()
    {
        return $this->trailingComments;
    }

    /**
     * Returns node location in the source code
     * 
     * @return SourceLocation
     */
    public function getLocation()
    {
        return $this->location;
    }
    
    /**
     * Sets the start position of the node in the source code
     * 
     * @param Position $position Start position
     * 
     * @return $this
     */
    public function setStartPosition(Position $position)
    {
        $this->location->start = $position;
        return $this;
    }
    
    /**
     * Sets the end position of the node in the source code
     * 
     * @param Position $position Start position
     * 
     * @return $this
     */
    public function setEndPosition(Position $position)
    {
        $this->location->end = $position;
        return $this;
    }
    
    /**
     * Traverses the current node and all its child nodes using the given
     * function
     * 
     * @param callable $fn      Function that will be called on each node
     * @param array    $options Options array. See Traverser class
     *                          documentation for available options
     * 
     * @return $this
     */
    public function traverse(callable $fn, $options = array())
    {
        $traverser = new \Peast\Traverser($options);
        $traverser->addFunction($fn)->traverse($this);
        return $this;
    }
    
    /**
     * Returns a serializable version of the node
     * 
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        $ret = array();
        $props = \Peast\Syntax\Utils::getNodeProperties($this);
        foreach ($props as $prop) {
            $ret[$prop["name"]] = $this->{$prop["getter"]}();
        }
        return $ret;
    }
    
    /**
     * Renders the current node
     * 
     * @param \Peast\Formatter\Base $formatter Formatter to use for the
     *                                         rendering
     * 
     * @return string
     */
    public function render(\Peast\Formatter\Base $formatter)
    {
        $renderer = new \Peast\Renderer();
        return $renderer->setFormatter($formatter)->render($this);
    }
    
    /**
     * Asserts that the given value is an array of defined type
     * 
     * @param mixed        $params    Value to check
     * @param string|array $classes   Class or array of classes to check against
     * @param bool         $allowNull If true, null values are allowed
     * 
     * @return void
     * 
     * @codeCoverageIgnore
     */
    protected function assertArrayOf($params, $classes, $allowNull = false)
    {
        if (!is_array($classes)) {
            $classes = array($classes);
        }
        if (!is_array($params)) {
            $this->typeError($params, $classes, $allowNull, true);
        } else {
            foreach ($params as $param) {
                foreach ($classes as $class) {
                    if ($param === null && $allowNull) {
                        continue 2;
                    } else {
                        $c = "Peast\\Syntax\\Node\\$class";
                        if ($param instanceof $c) {
                            continue 2;
                        }
                    }
                }
                $this->typeError($param, $classes, $allowNull, true, true);
            }
        }
    }
    
    /**
     * Asserts that the given value respects the defined type
     * 
     * @param mixed        $param     Value to check
     * @param string|array $classes   Class or array of classes to check against
     * @param bool         $allowNull If true, null values are allowed
     * 
     * @return void
     * 
     * @codeCoverageIgnore
     */
    protected function assertType($param, $classes, $allowNull = false)
    {
        if (!is_array($classes)) {
            $classes = array($classes);
        }
        if ($param === null) {
            if (!$allowNull) {
                $this->typeError($param, $classes, $allowNull);
            }
        } else {
            foreach ($classes as $class) {
                $c = "Peast\\Syntax\\Node\\$class";
                if ($param instanceof $c) {
                    return;
                }
            }
            $this->typeError($param, $classes, $allowNull);
        }
    }
    
    /**
     * Throws an error if the defined type is not supported b
     * 
     * @param mixed $param        The value to check
     * @param mixed $allowedTypes Class or array of classes to check against
     * @param bool  $allowNull    If true, null values are allowed
     * @param bool  $array        If true, the value must be an array
     * @param bool  $inArray      If true, the value is an array but the content
     *                            does not respects the type
     * 
     * @return void
     * 
     * @throws \TypeError
     * 
     * @codeCoverageIgnore
     */
    protected function typeError(
        $param, $allowedTypes, $allowNull = false, $array = false,
        $inArray = false
    ) {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $method = $backtrace[2]["class"] . "::" . $backtrace[2]["function"];
        $msg = "Argument 0 passed to $method must be ";
        if ($array) {
            $msg .= "an array of ";
        }
        $msg .= implode(" or ", $allowedTypes);
        if ($allowNull) {
            $msg .= " or null";
        }
        if (is_object($param)) {
            $parts = explode("\\", get_class($param));
            $type = array_pop($parts);
        } else {
            $type = gettype($param);
        }
        if ($inArray) {
            $type = "array of $type";
        }
        $msg .= ", $type given";
        if (version_compare(phpversion(), '7', '>=')) {
            throw new \TypeError($msg);
        } else {
            trigger_error($msg, E_USER_ERROR);
        }
    }
}