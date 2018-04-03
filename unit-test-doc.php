<?php
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
	require __DIR__ . '/vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../../autoload.php')) {
	require __DIR__ . '/../../autoload.php';
}

$project = empty($argv[1]) ? '.' : $argv[1];

/* @var $loader Composer\Autoload\ClassLoader */
$loader = addComposerLoader($project . '/vendor/autoload.php');

$map = $loader->getClassMap();

$function = function (ReflectionClass $class) {
    if ($class->newInstanceWithoutConstructor() instanceof PHPUnit\Framework\TestCase) {
        $card = [
            'title'              => '',
            'description'        => '',
            'module'             => '',
            'module-description' => '',
            'pre-conditions'     => [],
            'steps'              => [],
        ];
        $doc = $class->getDocComment();
        $card['module'] = $class->getName();
        if ($doc) {
            $card['module-description'] = docBlockParse($doc)->getSummary();
        }
        /* @var $method ReflectionMethod */
        foreach ($class->getMethods() as $method) {
            if (strtolower(substr($method->getName(), 0)) === 'setup') {
                $comments = getInlineCommentsInCode(getCodeFromReflectionMethod($method), '');
                $preCondition = docBlockParse($method->getDocComment())->getSummary();
                $card['pre-conditions'][$preCondition] = $comments;
            }
        }
        /* @var $method ReflectionMethod */
        foreach ($class->getMethods() as $method) {
            if (strtolower(substr($method->getName(), 0, 4)) === 'test') {
                $testDoc = $method->getDocComment();
                $card['title'] = $testDoc ? docBlockParse($testDoc)->getSummary() : $method->getName();
                $card['description'] = $testDoc ? docBlockParse($testDoc)->getDescription() : '';
                $comments = getInlineCommentsInCode(getCodeFromReflectionMethod($method), '');
                $card['steps'] = [[]];
                $card['results'] = [[]];
                $index = 0;
                $type = 0;
                foreach ($comments as $comment) {
                    if (strpos($comment[0], 'Assertion:') === 0) {
                        $type = 1;
                        $card['results'][$index][] = trim(substr($comment[0], 7));
                    } else {
                        if ($type) {
                            $index++;
                            $card['results'][$index] = [];
                        }
                        $type = 0;
                        $card['steps'][$index][] = $comment;
                    }
                }
                include __DIR__ . '/template.php';
            }
        }
    }
};
array_filter(findClasses("$project/tests/Feature", 'Tests\Feature'), $function);
