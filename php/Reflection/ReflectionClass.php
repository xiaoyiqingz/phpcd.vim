<?php

namespace PHPCD\Reflection;

class ReflectionClass extends \ReflectionClass
{
    /**
     * Get methods available for given class
     * depending on context
     *
     * @param bool|null $static Show static|non static|both types
     * @param bool public_only restrict the result to public methods
     * @return ReflectionMethod[]
     */
    public function getAvailableMethods($static, $public_only = false)
    {
        $methods = $this->getMethods();

        foreach ($methods as $key => $method) {
            if (false === $this->filter($method, $static, $public_only)) {
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
    public function getAvailableProperties($static, $public_only = false)
    {
        $properties = $this->getProperties();

        foreach ($properties as $key => $property) {
            if (false === $this->filter($property, $static, $public_only)) {
                unset($properties[$key]);
            }
        }

        return $properties;
    }

    /**
     * @param \ReflectionMethod|\ReflectionProperty $element
     * @return bool
     */
    private function filter($element, $static, $public_only)
    {
        if (!$element instanceof \ReflectionMethod && !$element instanceof \ReflectionProperty) {
            throw new \InvalidArgumentException(
                'Parameter must be a member of ReflectionMethod or ReflectionProperty class'
            );
        }

        if ($static !== null && ($element->isStatic() xor $static)) {
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

    public function getPseudoProperties()
    {
        $properties = [];

        foreach ($this->getAllClassDocComments() as $file_name => $doc) {
            $has_doc = preg_match_all('/@property(|-read|-write)\s+(?<types>\S+)\s+\$?(?<names>[a-zA-Z0-9_$]+)/mi', $doc, $matches);

            if (!$has_doc) {
                continue;
            }

            foreach ($matches['names'] as $idx => $name) {
                $properties[$name] = [
                    'type' => $matches['types'][$idx],
                    'file' => $file_name,
                ];
            }
        }

        return $properties;
    }

    public function getPseudoMethods()
    {
        $methods = [];

        foreach ($this->getAllClassDocComments() as $file_name => $doc) {
            $has_doc = preg_match_all('/@method\s+(?<statics>static)?\s*(?<types>\S+)\s+(?<names>[a-zA-Z0-9_$]+)\((?<params>.*)\)/mi', $doc, $matches);

            if (!$has_doc) {
                continue;
            }

            foreach ($matches['names'] as $idx => $name) {
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
