<?php

class PHPCD extends RpcServer
{
    public function info($class_name, $pattern, $mode) {
        if ($class_name) {
            return $this->classInfo($class_name, $pattern, $mode);
        } elseif($pattern) {
            return $this->functionOrConstantInfo($pattern);
        } else {
            return [];
        }
    }

    /**
     * 获取函数或者类成员方法的源代码位置（文件路径和行号）
     *
     * @param string $class_name 类名，传空值则表示函数
     * @param string $method_name 函数名或者方法名
     *
     * @return [ path, line ]
     */
    public function location($class_name, $method_name = null)
    {
        if ($class_name) {
            return $this->locationClass($class_name, $method_name);
        } else {
            return $this->locationFunction($method_name);
        }
    }

    private function locationClass($class_name, $method_name = null)
    {
        try {
            $class = new ReflectionClass($class_name);
            if (!$method_name) {
                return [
                    $class->getFileName(),
                    $class->getStartLine(),
                ];
            }

            $method  = $class->getMethod($method_name);

            if ($method) {
                return [
                    $method->getFileName(),
                    $method->getStartLine(),
                ];
            }
        } catch (ReflectionException $e) {
        }

        return [
            '',
            null,
        ];
    }

    /**
     * 获取类成员方法、成员变量或者函数的注释块
     *
     * @param string $class_name 类名，传空值则表示第二个参数为函数名
     * @param string $name 函数名或者成员名
     */
    public function doc($class_name, $name)
    {
        if ($class_name && $name) {
            list($path, $doc) = $this->docClass($class_name, $name);
        } elseif ($name) {
            list($path, $doc) = $this->docFunction($name);
        }

        if ($doc) {
            return [$path, $this->clearDoc($doc)];
        } else {
            return [null, null];
        }
    }

    /**
     * 获取 PHP 文件的名称空间和 use 列表
     *
     * @param string $path 文件路径
     *
     * @return [
     *   'namespace' => 'ns',
     *   'imports' => [
     *     'alias1' => 'fqdn1',
     *   ]
     * ]
     */
    public function nsuse($path)
    {
        $file = new SplFileObject($path);
        $s = [
            'namespace' => '',
            'imports' => [
            ],
        ];
        foreach ($file as $line) {
            if (preg_match('/\b(class|interface|trait)\b/i', $line)) {
                break;
            }
            $line = trim($line);
            if (!$line) {
                continue;
            }
            if (preg_match('/(<\?php)?\s*namespace\s+(.*);$/', $line, $matches)) {
                $s['namespace'] = $matches[2];
            } elseif (strtolower(substr($line, 0, 3) == 'use')) {
                $as_pos = strripos($line, ' as ');
                if ($as_pos !== false) {
                    $alias = trim(substr($line, $as_pos + 3, -1));
                    $s['imports'][$alias] = trim(substr($line, 3, $as_pos - 3));
                } else {
                    $slash_pos = strripos($line, '\\');
                    if ($slash_pos === false) {
                        $alias = trim(substr($line, 4, -1));
                    } else {
                        $alias = trim(substr($line, $slash_pos + 1, -1));
                    }
                    $s['imports'][$alias] = trim(substr($line, 4, -1));
                }
            }
        }

        return $s;
    }

    private function classInfo($class_name, $pattern, $mode)
    {
        $reflection = new ReflectionClass($class_name);
        $items = [];

        foreach ($reflection->getConstants() as $name => $value) {
            $items[] = [
                'word' => $name,
                'abbr' => "+ @ $name = $value",
                'kind' => 'd',
                'icase' => 1,
            ];
        }

        if ($mode == 1) {
            $methods = $reflection->getMethods(ReflectionMethod::IS_STATIC);
        } else {
            $methods = $reflection->getMethods();
        }
        foreach ($methods as $method) {
            $info = $this->getMethodInfo($method, $pattern);
            if ($info) {
                $items[] = $info;
            }
        }

        if ($mode == 1) {
            $properties = $reflection->getProperties(ReflectionProperty::IS_STATIC);
        } else {
            $properties = $reflection->getProperties();
        }

        foreach ($properties as $property) {
            $info = $this->getPropertyInfo($property, $pattern);
            if ($info) {
                $items[] = $info;
            }
        }

        return $items;
    }

    private function functionOrConstantInfo($pattern)
    {
        $items = [];
        $funcs = get_defined_functions();
        foreach ($funcs['internal'] as $func) {
            $info = $this->getFunctionInfo($func, $pattern);
            if ($info) {
                $items[] = $info;
            }
        }
        foreach ($funcs['user'] as $func) {
            $info = $this->getFunctionInfo($func, $pattern);
            if ($info) {
                $items[] = $info;
            }
        }

        return array_merge($items, $this->getConstantsInfo($pattern));
    }

    private function getConstantsInfo($pattern)
    {
        $items = [];
        foreach (get_defined_constants() as $name => $value) {
            if ($pattern && strpos($name, $pattern) !== 0) {
                continue;
            }

            $items[] = [
                'word' => $name,
                'abbr' => "@ $name = $value",
                'kind' => 'd',
                'icase' => 0,
            ];
        }

        return $items;
    }

    private function getFunctionInfo($name, $pattern = null)
    {
        if ($pattern && strpos($name, $pattern) !== 0) {
            return null;
        }

        $reflection = new ReflectionFunction($name);
        $params = array_map(function ($param) {
            return $param->getName();
        }, $reflection->getParameters());

        return [
            'word' => $name,
            'abbr' => "$name(" . join(', ', $params) . ')',
            'info' => preg_replace('#/?\*(\*|/)?#','', $reflection->getDocComment()),
            'kind' => 'f',
            'icase' => 1,
        ];
    }

    private function getPropertyInfo($property, $pattern)
    {
        $name = $property->getName();
        if ($pattern && strpos($name, $pattern) !== 0) {
            return null;
        }
        $modifier = $this->getModifier($property);

        return [
            'word' => $name,
            'abbr' => "$modifier $name",
            'info' => preg_replace('#/?\*(\*|/)?#','', $property->getDocComment()),
            'kind' => 'p',
            'icase' => 1,
        ];
    }

    private function getMethodInfo($method, $pattern = null)
    {
        $name = $method->getName();
        if ($pattern && strpos($name, $pattern) !== 0) {
            return null;
        }
        $params = array_map(function ($param) {
            return $param->getName();
        }, $method->getParameters());

        $modifier = $this->getModifier($method);

        return [
            'word' => $name,
            'abbr' => "$modifier $name (" . join(', ', $params) . ')',
            'info' => $this->clearDoc($method->getDocComment()),
            'kind' => 'f',
            'icase' => 1,
        ];
    }

    private function getModifier($reflection)
    {
        $modifier = '';

        if ($reflection->isPublic()) {
            $modifier = '+';
        } elseif ($reflection->isProtected()) {
            $modifier = '#';
        } elseif ($reflection->isPrivate()) {
            $modifier = '-';
        } elseif ($reflection->isFinal()) {
            $modifier = '!';
        }

        $static = $reflection->isStatic() ? '@' : ' ';

        return "$modifier $static";
    }

    private function locationFunction($name)
    {
        $func = new ReflectionFunction($name);
        return [
            $func->getFileName(),
            $func->getStartLine(),
        ];
    }

    private function docClass($class_name, $name)
    {
        if (!class_exists($class_name)) {
            return ['', ''];
        }

        $class = new ReflectionClass($class_name);
        if ($class->hasProperty($name)) {
            $property = $class->getProperty($name);
            return [
                $class->getFileName(),
                $property->getDocComment()
            ];
        } elseif ($class->hasMethod($name)) {
            $method = $class->getMethod($name);
            return [
                $class->getFileName(),
                $method->getDocComment()
            ];
        }
    }

    private function docFunction($name)
    {
        if (!function_exists($name)) {
            return ['', ''];
        }

        $function = new ReflectionFunction($name);

        return [
            $function->getFileName(),
            $function->getDocComment()
        ];
    }

    private function clearDoc($doc)
    {
        $doc = preg_replace('/[ \t]*\* ?/m','', $doc);
        return preg_replace('#\s*\/|/\s*#','', $doc);
    }

}
