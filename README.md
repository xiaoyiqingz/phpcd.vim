[English](README_en.md)

[![asciicast](https://asciinema.org/a/4dzyyjymrguylqt21igxlhhqx.png)](https://asciinema.org/a/4dzyyjymrguylqt21igxlhhqx)

## 简介

PHPCD，全称 PHP Completion Daemon，译为 PHP 补全服务。PHPCD 实现了 Vim 的 omni
补全接口，提供 PHP 相关的智能补全和定义跳转服务。

PHPCD 的 VimL 部分基于[phpcomplete.vim](https://github.com/shawncplus/phpcomplete.vim)，
感谢原项目贡献者的努力。

因为 PHPCD 利用 PHP 的 [反射机制](http://php.net/manual/en/book.reflection.php)进行补全和跳转，
所以 PHPCD 几乎不需要事先生成索引文件，启动速度、补全速度和跳转速度都非常快，代码也很简洁。

PHPCD 只能配合[NeoVim](http://neovim.io/) 工作，这是一个艰难的抉择。

##  特色
 * 快、轻、强
 * 真正区分类方法`Class::method()`和成员方法`$class->method()`
 * 真正支持`self::` 和 `$this->` 上下文
 * 支持多种类型检测：
     - 支持`/* @var $yourvar YourClass */`类型注释
     - 支持`$instance = new Class;`类初始化
     - 支持`$instance = Class::getInstance();`单例模式初始化
     - 支持`$date = DateTime::createFromFormat(...)`内建类
     - 支持函数（全局函数、成员函数和匿名函数）参数的类型提示
     - 支持`@param`
     - 支持`@return`
 * 补全成员方法和成员属性的时候自动显示块注释
 * 支持内建类的方法、属性、常量的补全
 * 增强型定义跳转<kbd>ctrl</kbd>+<kbd>]</kbd>

## 安装指南

### 环境要求
 1. PHP 5.3+
 2. 开启 [socket](http://php.net/manual/en/book.sockets.php) 扩展
 3. 开启 [PCNTL](http://php.net/manual/en/book.pcntl.php) 扩展
 4. 开启 [Msgpack 0.5.7+](https://github.com/msgpack/msgpack-php) 扩展
 5. 开启 [Composer](https://getcomposer.org/) 支持
 6. NeoVim

### 安装 Msgpack 扩展

Msgpack 扩展需要 0.5.7 以及以上版本。

```
git clone https://github.com/msgpack/msgpack-php.git
cd msgpack-php
phpize
./configure
make
sudo make install
```

在 php.ini 启用 Msgpack：
```
extension=msgpack.so
```

### 安装 vim-plug

推荐使用[Vim-Plug](https://github.com/junegunn/vim-plug/blob/master/README.md)管理 Vim 插件。

安装 Vim-Plug 后，可以添加：

```
Plug 'lvht/phpcd.vim'
Plug 'vim-scripts/progressbar-widget' " 用来显示索引进度的插件
```

然后执行`:PlugInstall`进行安装。

最后，在配置文件中指定 PHPCD 为 omni 补全引擎：

```
autocmd FileType php setlocal omnifunc=phpcd#CompletePHP
```

## 使用方法
首先运行 `composer install` 安装项目依赖，然后在项目根目录运行 NeoVim。

打开一个 php 文件，如果一切正常的话，几秒钟后 Vim 状态栏会显示进度条。
进度条走完则可开始使用。

智能补全按<kbd>Ctrl</kbd>+<kbd>x</kbd><kbd>Ctrl</kbd>+<kbd>o</kbd>，
智能跳转按<kbd>ctrl</kbd>+<kbd>]</kbd>。
