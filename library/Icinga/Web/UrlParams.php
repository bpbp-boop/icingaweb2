<?php

namespace Icinga\Web;

class UrlParams
{
    protected $separator = '&';

    protected $params = array();

    protected $index = array();

    public function isEmpty()
    {
        return empty($this->index);
    }

    public function setSeparator($separator)
    {
        $this->separator = $separator;
        return $this;
    }

    /**
     * Get the given parameter
     *
     * Returns the last URL param if defined multiple times, $default if not
     * given at all
     *
     * @param string $param   The parameter you're interested in
     * @param string $default An optional default value
     *
     * @return mixed
     */
    public function get($param, $default = null)
    {
        if (! $this->has($param)) {
            return $default;
        }

        return rawurldecode($this->params[ end($this->index[$param]) ][ 1 ]);
    }

    /**
     * Get all instances of the given parameter
     *
     * Returns an array containing all values defined for a given parameter,
     * $default if none.
     *
     * @param string $param   The parameter you're interested in
     * @param string $default An optional default value
     *
     * @return mixed
     */
    public function getValues($param, $default = array())
    {
        if (! $this->has($param)) {
            return $default;
        }

        $ret = array();
        foreach ($this->index[$param] as $key) {
            $ret[] = rawurldecode($this->params[$key][1]);
        }
        return $ret;
    }

    /**
     * Whether the given parameter exists
     *
     * Returns true if such a parameter has been defined, false otherwise.
     *
     * @param string $param The parameter you're interested in
     *
     * @return boolean
     */
    public function has($param)
    {
        return array_key_exists($param, $this->index);
    }

    /**
     * Get and remove the given parameter
     *
     * Returns the last URL param if defined multiple times, $default if not
     * given at all. The parameter will be removed from this object.
     *
     * @param string $param   The parameter you're interested in
     * @param string $default An optional default value
     *
     * @return mixed
     */
    public function shift($param = null, $default = null)
    {
        if ($param === null) {
            if (empty($this->params)) {
                return $default;
            }
            $ret = array_shift($this->params);
            $ret[0] = rawurldecode($ret[0]);
            $ret[1] = rawurldecode($ret[1]);
        } else {
            if (! $this->has($param)) {
                return $default;
            }
            $key = reset($this->index[$param]);
            $ret = rawurldecode($this->params[$key][1]);
            unset($this->params[$key]);
        }

        $this->reIndexAll();
        return $ret;
    }

    /**
     * Add the given parameter with the given value
     *
     * This will add the given parameter, regardless of whether it already
     * exists.
     *
     * @param string $param The parameter you're interested in
     * @param string $value The value to be stored
     *
     * @return self
     */
    public function add($param, $value)
    {
        $this->params[] = array($param, $this->cleanupValue($value));
        $this->indexLastOne();
        return $this;
    }

    /**
     * Adds a list of parameters
     *
     * This may be used with either a list of values for a single parameter or
     * with a list of parameter / value pairs.
     *
     * @param string $param Parameter name or param/value list
     * @param string $value The value to be stored
     *
     * @return self
     */
    public function addValues($param, $values = null)
    {
        if ($values === null && is_array($param)) {
            foreach ($param as $k => $v) {
                $this->addValue($k, $v);
            }
        } else {
            foreach ($values as $value) {
                $this->addValue($param, $value);
            }
        }

        return $this;
    }

    /**
     * Add the given parameter with the given value in front of all other values
     *
     * This will add the given parameter in front of all others, regardless of
     * whether it already exists.
     *
     * @param string $param The parameter you're interested in
     * @param string $value The value to be stored
     *
     * @return self
     */
    public function unshift($param, $value)
    {
        array_unshift($this->params, array($param, $this->cleanupValue($value)));
        $this->reIndexAll();
        return $this;
    }

    /**
     * Set the given parameter with the given value
     *
     * This will set the given parameter, and override eventually existing ones.
     *
     * @param string $param The parameter you want to set
     * @param string $value The value to be stored
     *
     * @return self
     */
    public function set($param, $value)
    {
        if (! $this->has($param)) {
            return $this->add($param, $value);
        }

        while (count($this->index[$param]) > 1) {
            $remove = array_pop($this->index[$param]);
            unset($this->params[$remove]);
        }

        $this->params[$this->index[$param][0]] = array($param, $this->cleanupValue($value));
        $this->reIndexAll();

        return $this;        
    }

    public function remove($param)
    {
        $changed = false;

        if (! is_array($param)) {
            $param = array($param);
        }

        foreach ($param as $p) {
            if ($this->has($p)) {
                foreach ($this->index[$p] as $key) {
                    unset($this->params[$key]);
                }
                $this->changed = true;
            }
        }

        if ($changed) {
            $this->reIndexAll();
        }

        return $this;
    }

    public function without($param)
    {
        $params = clone $this;
        return $params->remove($param);
    }

    // TODO: push, pop?

    protected function indexLastOne()
    {
        end($this->params);
        $key = key($this->params);
        $param = $this->params[$key][0];
        $this->addParamToIndex($param, $key);
    }

    protected function addParamToIndex($param, $key)
    {
        if (! $this->has($param)) {
            $this->index[$param] = array();
        }
        $this->index[$param][] = $key;
    }

    protected function reIndexAll()
    {
        $this->index = array();
        $this->params = array_values($this->params);
        foreach ($this->params as $key => & $param) {
            $this->addParamToIndex($param[0], $key);
        }
    }

    protected function cleanupValue($value)
    {
        return is_bool($value) ? $value : (string) $value;
    }

    protected function parseQueryString($queryString)
    {
        $parts = preg_split('~&~', $queryString, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($parts as $part) {
            $this->parseQueryStringPart($part);
        }
    }

    protected function parseQueryStringPart($part)
    {
        if (strpos($part, '=') === false) {
            $this->add($part, true);
        } else {
            list($key, $val) = preg_split('/=/', $part, 2);
            $this->add($key, $val);
        }
    }

    public function __toString()
    {
        $parts = array();
        foreach ($this->params as $p) {
            if ($p[1] === true) {
                $parts[] = $p[0];
            } else {
                $parts[] = $p[0] . '=' . $p[1];
            }
        }
        return implode($this->separator, $parts);
    }

    public static function fromQueryString($queryString = null)
    {
        if ($queryString === null) {
            $queryString = $_SERVER['QUERY_STRING'];
        }
        $params = new static();
        $params->parseQueryString($queryString);

        return $params;
    }
}