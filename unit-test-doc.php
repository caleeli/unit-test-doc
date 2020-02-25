<html>
<head>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <style>
        td {
            vertical-align: top;
        }
        body {
        }
    </style>
</head>
<body>
<?php
ob_start();
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
$doneClasses = [];

$function = function (ReflectionClass $class) use (&$indexList, &$doneClasses) {
    if (in_array($class->getName(), $doneClasses)) {
        return;
    }
    $doneClasses[] = $class->getName();
    if ((class_exists(Tests\TestCase::class) && $class->isSubclassOf(Tests\TestCase::class))
        || (class_exists(PHPUnit\Framework\TestCase::class) && $class->isSubclassOf(PHPUnit\Framework\TestCase::class))) {
        $card = [
            'title' => '',
            'description' => '',
            'module' => '',
            'module-description' => '',
            'pre-conditions' => [],
            'steps' => [],
        ];
        $doc = $class->getDocComment();
        $card['module'] = $class->getName();
        if ($doc) {
            $card['module'] = docBlockParse($doc)->getSummary();
            $card['module-description'] = docBlockParse($doc)->getDescription();
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
                $testDoc = $testDoc ? docBlockParse($testDoc) : null;
                $diagram = $testDoc->getTagsByName('diagram');
                $card['title'] = $testDoc ? $testDoc->getSummary() : $method->getName();
                $card['description'] = $testDoc ? $testDoc->getDescription() : '';
                $card['diagram'] = $diagram ? diagramSvg(md5($class->getName() . '::' . $method->getName()) . '.svg', $diagram[0]) : null;
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
                $indexList[$card['module']][$indexTitle] = $card['title'];
                include __DIR__ . '/template.php';
            }
        }
    }
};
$basePath = realpath('./tests');
foreach ($map as $class => $file) {
    if (strpos(realpath($file), $basePath) !== 0) {
        continue;
    }
    $reflection = new ReflectionClass($class);
    $function($reflection);
}
foreach ($loader->getPrefixesPsr4() as $namespace => $paths) {
    foreach ($paths as $path) {
        if (strpos(realpath($path), $basePath) !== 0) {
            continue;
        }
        array_filter(findClasses($path, $namespace), $function);
    }
}
$content = ob_get_contents();
ob_end_clean();
?>
<?php
ob_start();
foreach ($indexList as $module => $index):
?>
    <b><?php echo htmlentities($module, ENT_NOQUOTES); ?></b><br>
<?php
    foreach ($index as $id => $title):
?>
    <a href="#<?php echo htmlentities($id, ENT_QUOTES); ?>">
        <?php echo htmlentities($title, ENT_NOQUOTES); ?>
    </a><br>
<?php
endforeach;
endforeach;
$index = ob_get_contents();
ob_end_clean();
?>
    <div class="container-fluid" style="height:100vh;overflow:hidden">
      <div class="row">
        <nav class="col-md-3 d-none d-md-block bg-light sidebar">
          <div class="sidebar-sticky" style="height:100vh;overflow:auto;">
              <?php echo $index; ?>
          </div>
        </nav>

        <main role="main" class="col-md-9 ml-sm-auto col-lg-9 px-4">
          <div class="" style="height:100vh;overflow:auto;">
            <?php echo $content; ?>
          </div>
        </main>
      </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
</body>
</html>