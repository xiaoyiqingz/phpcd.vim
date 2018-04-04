<?php

namespace PHPCD\Reflection;

class ReflectionClass extends \ReflectionClass
{
    private $matcher;

    public function setMatcher(\PHPCD\Matcher\Matcher $matcher)
    {
        $this->matcher = $matcher;
    }

    public function getAvailableConstants($pattern)
    {
        $constants = $this->getConstants();

        foreach ($constants as $name => $value) {
            if (!$this->matcher->match($pattern, $name)) {
                unset($constants[$name]);
            }
        }

        return $constants;
    }

    /**
     * Get methods available for given class
     * depending on context
     *
     * @param bool|null $static Show static|non static|both types
     * @param bool public_only restrict the result to public methods
     * @return ReflectionMethod[]
     */
    public function getAvailableMethods($static, $public_only, $pattern)
    {
        $methods = $this->getMethods();

        foreach ($methods as $key => $method) {
            if (!$this->filter($method, $static, $public_only, $pattern)) {
                unset($methods[$key]);
            }
        }

        return $methods;
    }

    /**
     * Get properties available for given class
     * depending on context
     *
     * @param bool|null $static Show static|non static|both types
     * @param bool public_only restrict the result to public properties
     * @return ReflectionProperty[]
     */
    public function getAvailableProperties($static, $public_only, $pattern)
    {
        $properties = $this->getProperties();

        foreach ($properties as $key => $property) {
            if (!$this->filter($property, $static, $public_only, $pattern)) {
                unset($properties[$key]);
            }
        }

        return $properties;
    }

    private function filter($element, $static, $public_only, $pattern)
    {
        if (!$this->matcher->match($pattern, $element->getName())) {
            return false;
        }

        if ($static !== null && ($element->isStatic() || $static)) {
            return false;
        }

        if ($element->isPublic()) {
            return true;
        }

        if ($public_only) {
            return false;
        }

        if ($element->isProtected()) {
            return true;
        }

        // $element is then private
        return $element->getDeclaringClass()->getName() === $this->getName();
    }

    private function getAllClassDocComments()
    {
        $reflection = $this;
        $doc = [];

        do {
            $file_name = $reflection->getFileName();
            $doc[$file_name] = $reflection->getDocComment();
            $reflection = $reflection->getParentClass();
        } while ($reflection); // gets the parents properties too

        return $doc;
    }

    public function getPseudoProperties($pattern)
    {
        $properties = [];

        foreach ($this->getAllClassDocComments() as $file_name => $doc) {
            $has_doc = preg_match_all('/@property(|-read|-write)\s+(?<types>\S+)\s+\$?(?<names>[a-zA-Z0-9_$]+)/mi', $doc, $matches);

            if (!$has_doc) {
                continue;
            }

            foreach ($matches['names'] as $idx => $name) {
                if (!$this->matcher->match($pattern, $name)) {
                    continue;
                }

                $properties[$name] = [
                    'type' => $matches['types'][$idx],
                    'file' => $file_name,
                ];
            }
        }

        return $properties;
    }

    public function getPseudoMethods($pattern)
    {
        $methods = [];

        foreach ($this->getAllClassDocComments() as $file_name => $doc) {
            $has_doc = preg_match_all('/@method\s+(?<statics>static)?\s*(?<types>\S+)\s+(?<names>[a-zA-Z0-9_$]+)\((?<params>.*)\)/mi', $doc, $matches);

            if (!$has_doc) {
                continue;
            }

            foreach ($matches['names'] as $idx => $name) {
                if (!$this->matcher->match($pattern, $name)) {
                    continue;
                }

                $methods[$name] = [
                    'file' => $file_name,
                    'type' => $matches['types'][$idx],
                    'params' => $matches['params'][$idx],
                ];
            }
        }

        return $methods;
    }
}
