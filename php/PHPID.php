<?php

class PHPID extends RpcServer
{
    public function setRoot($root)
    {
        $this->root = $root;
        return $this;
    }

    public function loop()
    {
        $this->index();
        parent::loop();
    }

    /**
     * 更新特定类的继承关系和接口实现关系
     * @param string $class_name 全限定类名
     */
    public function update($class_name)
    {
        list($parent, $interfaces) = $this->getClassInfo($class_name);

        if ($parent) {
            $this->updateParentIndex($parent, $class_name);
        }
        foreach ($interfaces as $interface) {
            $this->updateInterfaceIndex($interface, $class_name);
        }
    }

    /**
     * 获取接口实现类列表或者抽象类子类
     *
     * @param string $name 接口名或者抽象类名
     * @param bool $is_interface 是否为接口，抽象类则传 true
     *
     * @return [
     *   'full class name 1',
     * ]
     */
    public function ls($name, $is_interface = false)
    {
        $base_path = $is_interface ? $this->getIntefacesDir() : $this->getExtendsDir();
        $path = $base_path . '/' . $this->getIndexFileName($name);
        if (!is_file($path)) {
            return [];
        }

        $list = json_decode(file_get_contents($path));
        if (!is_array($list)) {
            return [];
        }

        sort($list);

        return $list;
    }

    /**
     * 解析 autoload_classmap.php 生成类的继承关系和接口的实现关系
     *
     * @param bool $is_force 是否强制重新刷新索引
     */
    public function index($is_force = false)
    {
        if (is_dir($this->getIndexDir()) && !$is_force) {
            return;
        }

        $this->initIndexDir();

        exec('composer dump-autoload -o -d ' . $this->root . ' 2>&1 >/dev/null');
        $this->class_map = require $this->root
            . '/vendor/composer/autoload_classmap.php';

        $pipe_path = sys_get_temp_dir() . '/' . uniqid();
        posix_mkfifo($pipe_path, 0600);

        $this->vimOpenProgressBar(count($this->class_map));

        while ($this->class_map) {
            $pid = pcntl_fork();

            if ($pid == -1) {
                die('could not fork');
            } elseif ($pid > 0) {
                // 父进程
                $pipe = fopen($pipe_path, 'r');
                $data = fgets($pipe);
                $this->class_map = json_decode(trim($data), true);
                pcntl_waitpid($pid, $status);
            } else {
                // 子进程
                $pipe = fopen($pipe_path, 'w');
                register_shutdown_function(function () use ($pipe) {
                    $data = json_encode($this->class_map, true);
                    fwrite($pipe, "$data\n");
                    fclose($pipe);
                });
                $this->_index();
                fwrite($pipe, "[]\n");
                fclose($pipe);
                exit;
            }
        }
        fclose($pipe);
        unlink($pipe_path);
        $this->vimCloseProgressBar();
    }

    private function getIndexDir()
    {
        return $this->root . '/.phpcd';
    }

    private function getIntefacesDir()
    {
        return $this->getIndexDir() . '/interfaces';
    }

    private function getExtendsDir()
    {
        return $this->getIndexDir() . '/extends';
    }

    private function initIndexDir()
    {
        $extends_dir = $this->getExtendsDir();
        if (!is_dir($extends_dir)) {
            mkdir($extends_dir, 0700, true);
        }

        $interfaces_dir = $this->getIntefacesDir();
        if (!is_dir($interfaces_dir)) {
            mkdir($interfaces_dir, 0700, true);
        }
    }

    private function _index()
    {
        foreach ($this->class_map as $class_name => $file_path) {
            unset($this->class_map[$class_name]);
            $this->vimUpdateProgressBar();
            require $file_path;
            list($parent, $interfaces) = $this->getClassInfo($class_name);
            if ($parent) {
                $this->updateParentIndex($parent, $class_name);
            }
            foreach ($interfaces as $interface) {
                $this->updateInterfaceIndex($interface, $class_name);
            }
        }
    }

    private function updateParentIndex($parent, $child)
    {
        $index_file = $this->getExtendsDir() . '/' . $this->getIndexFileName($parent);
        $this->saveChild($index_file, $child);
    }

    private function updateInterfaceIndex($interface, $implementation)
    {
        $index_file = $this->getIntefacesDir() . '/' . $this->getIndexFileName($interface);
        $this->saveChild($index_file, $implementation);
    }

    private function saveChild($index_file, $child)
    {
        if (is_file($index_file)) {
            $childs = json_decode(file_get_contents($index_file));
        } else {
            $childs = [];
        }

        $childs[] = $child;
        $childs = array_unique($childs);
        file_put_contents($index_file, json_encode($childs));
    }

    private function getIndexFileName($name)
    {
        return str_replace("\\", '_', $name);
    }

    private function getClassInfo($name) {
        try {
            $reflection = new ReflectionClass($name);

            $parent = $reflection->getParentClass();
            if ($parent) {
                $parent = $parent->getName();
            }

            $interfaces = array_keys($reflection->getInterfaces());

            return [$parent, $interfaces];
        } catch (ReflectionException $e) {
            return [null, []];
        }
    }

    private function vimOpenProgressBar($max)
    {
        $cmd = 'let g:pb = vim#widgets#progressbar#NewSimpleProgressBar("Indexing:", ' . $max . ')';
        $this->call('vim_command', [$cmd]);
    }

    private function vimUpdateProgressBar()
    {
        $this->call('vim_command', ['call g:pb.incr()']);
    }

    private function vimCloseProgressBar()
    {
        $this->call('vim_command', ['call g:pb.restore()']);
    }
}
