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
        $traverser->addVisitor(new \PhpParser\NodeVisitor\NameResolver);

        $visitor = new class extends \PhpParser\NodeVisitorAbstract
        {
            public $class = null;

            public function leaveNode(Node $node)
            {
                if ($node instanceof Node\Stmt\Class_ && isset($node->namespacedName)) {
                    $this->class = (string)$node->namespacedName;
                    return NodeTraverser::STOP_TRAVERSAL;
                }
            }
        };

        $traverser->addVisitor($visitor);

        try {
            $stmts = $parser->parse(file_get_contents($path));
            $stmts = $traverser->traverse($stmts);
        } catch (\Throwable $ignore) {
            // pass
        }

        return $visitor->class;
    }
}
