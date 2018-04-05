<?php
/**
 * Import a composer loader.
 *
 */
function addComposerLoader($path)
{
    return require $path;
}

/**
 *
 * @param string $docComment
 *
 * @return \phpDocumentor\Reflection\DocBlock
 */
function docBlockParse($docComment)
{
    $factory = \phpDocumentor\Reflection\DocBlockFactory::createInstance();
    return $factory->create($docComment);
}

/**
 *
 * @param ReflectionMethod $method
 *
 * @return string
 */
function getCodeFromReflectionMethod(ReflectionMethod $method)
{
    $source = explode("\n", file_get_contents($method->getFileName()));
    $res = array_slice($source, $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1);
    return implode("\n", $res);
}

/**
 *
 * @param string $code
 * @param string $prefix
 * @return string
 */
function getInlineCommentsInCode($code, $prefix)
{
    $tokens = token_get_all("<?php\n$code");
    $res = [];
    $code = [];
    $index = -1;
    foreach ($tokens as $token) {
        if (is_array($token) && $token[0] === T_COMMENT) {
            $res[] = [$prefix . trim(substr($token[1], 2)), ''];
            $index++;
        } elseif ($index >= 0) {
            $res[$index][1] .= is_array($token) ? $token[1] : $token;
        }
    }
    return $res;
}

/**
 * Find PSR4 classes in a path by namespace.
 *
 * @param string $path
 * @param string $namespace
 * @param ReflectionClass $res
 * @return \ReflectionClass
 */
function findClasses($path, $namespace, &$res = [])
{
    foreach (glob("$path/*.php") as $filename) {
        $name = basename($filename, '.php');
        $className = "$namespace$name";
        $res[] = new ReflectionClass($className);
    }
    foreach (glob("$path/*", GLOB_ONLYDIR) as $dir) {
        $name = basename($dir);
        findClasses("$path/$name", "$namespace$name\\", $res);
    }
    return $res;
}
