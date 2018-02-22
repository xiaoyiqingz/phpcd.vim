<?php
namespace PHPCD;

use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Node;
use PhpParser\NodeTraverser;

class Parser
{
    public static function getClassName($path)
    {
        $parser = (new \PhpParser\ParserFactory)->create(\PhpParser\ParserFactory::PREFER_PHP7);

        $traverser = new \PhpParser\NodeTraverser;
        $visitor = new ClassNameVisitor;
        $traverser->addVisitor(new \PhpParser\NodeVisitor\NameResolver);
        $traverser->addVisitor($visitor);

        try {
            $stmts = $parser->parse(file_get_contents($path));
            $stmts = $traverser->traverse($stmts);
        } catch (\Throwable $ignore) {
        }

        return $visitor->name;
    }
}
