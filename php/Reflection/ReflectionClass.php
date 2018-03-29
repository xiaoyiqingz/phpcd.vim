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

    public function getAllClassDocComments()
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

    public function getPseudoProperties(bool $disable_modifier = false)
    {
        $doc = $this->getAllClassDocComments();
        $all_docs = '';
        foreach ($doc as $class_doc) {
            $all_docs .= $class_doc;
        }

        $has_doc = preg_match_all('/@property(|-read|-write)\s+(?<types>\S+)\s+\$?(?<names>[a-zA-Z0-9_$]+)/mi', $all_docs, $matches);
        if (!$has_doc) {
            return [];
        }

        $items = [];
        foreach ($matches['names'] as $idx => $name) {
            $items[] = [
                'word' => $name,
                'abbr' => $disable_modifier ? $name : sprintf('%3s %s', '+', $name),
                'info' => $matches['types'][$idx],
                'kind' => 'p',
                'icase' => 1,
            ];
        }

        return $items;
    }

    public function getPseudoMethods(bool $disable_modifier = false)
    {
        $doc = $this->getAllClassDocComments();
        $all_docs = '';
        foreach ($doc as $class_doc) {
            $all_docs .= $class_doc;
        }

        $has_doc = preg_match_all('/@method\s+(?<statics>static)?\s*(?<types>\S+)\s+(?<names>[a-zA-Z0-9_$]+)\((?<params>.*)\)/mi', $all_docs, $matches);
        if (!$has_doc) {
            return [];
        }

        $items = [];
        foreach ($matches['names'] as $idx => $name) {
            preg_match_all('/\$[a-zA-Z0-9_]+/mi', $matches['params'][$idx], $params);

            if ($disable_modifier) {
                $abbr = sprintf("%s(%s)", $name, join(', ', end($params)));
            } else {
                $abbr = sprintf("%3s %s(%s)", '+', $name, join(', ', end($params)));
            }

            $items[] = [
                'word' => $name,
                'abbr' => $abbr,
                'info' => $matches['types'][$idx],
                'kind' => 'f',
                'icase' => 1,
            ];
        }

        return $items;
    }
}
