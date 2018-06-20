<html>
<head>
    <style>
        td {
            vertical-align: top;
        }
        body {
            width: 8in;
        }
    </style>
</head>
<body>
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
$indexList = [];

$function = function (ReflectionClass $class) use (&$indexList) {
    if (!$class->isSubclassOf(PHPUnit\Framework\TestCase::class)) {
        return;
    }

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
                $preCondition = $method->getDocComment() ? docBlockParse($method->getDocComment())->getSummary() : 'Missing doc block for ' . $method->getDeclaringClass()->getName() . '::' . $method->getName();
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
                $lastType = 0;
                foreach ($comments as $comment) {
                    preg_match('/ +/', $comment[2][1], $sp);
                    $indent = strlen($sp[0]);
                    $typeA = strpos($comment[0], 'Assertion:') === 0 ? 1 : 0;
                    if ($indent > 8) {
                        $typeA = $lastType;
                    }
                    if ($typeA) { //strpos($comment[0], 'Assertion:') === 0) {
                        $type = 1;
                        $card['results'][$index][] = trim(substr($comment[0], 10));
                    } else {
                        if ($type) {
                            $index++;
                            $card['results'][$index] = [];
                        }
                        $type = 0;
                        $card['steps'][$index][] = $comment;
                    }
                    $lastType = $type;
                }
                $indexTitle = preg_replace('/\W+/', '_', $card['title']);
                $indexList[$indexTitle] = $card['title'];
                include __DIR__ . '/template.php';
            }
        }
    }
};
$basePath = realpath('./tests');
foreach ($map as $class => $file) {
    if (strpos(realpath($file), $basePath)!==0) {
        continue;
    }
    $reflection = new ReflectionClass($class);
    $function($reflection);
}
foreach($loader->getPrefixesPsr4() as $namespace=>$paths) {
    foreach($paths as $path) {
        if (strpos(realpath($path), $basePath)!==0) {
            continue;
        }
        array_filter(findClasses($path, $namespace), $function);
    }
}

?>
<?php
foreach($indexList as $id => $title):
?>
    <a href="#<?php echo htmlentities($id, ENT_QUOTES); ?>">
        <?php echo htmlentities($title, ENT_NOQUOTES); ?>
    </a><br>
<?php
endforeach;
?>
</body>
</html>