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
    foreach ($tokens as $i => $token) {
        if (is_array($token) && $token[0] === T_COMMENT) {
            $res[] = [$prefix . trim(substr($token[1], 2)), '', $tokens[$i - 1]];
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

/**
 * Highlight a code text
 *
 * @param string $text
 *
 * @return string
 */
function highlightText($text)
{
    $text = highlight_string('<?php ' . $text, true);  // highlight_string() requires opening PHP tag or otherwise it will not colorize the text
    $text = trim($text);
    $text = preg_replace('|^\\<code\\>\\<span style\\="color\\: #[a-fA-F0-9]{0,6}"\\>|', '', $text, 1);  // remove prefix
    $text = preg_replace('|\\</code\\>$|', '', $text, 1);  // remove suffix 1
    $text = trim($text);  // remove line breaks
    $text = preg_replace('|\\</span\\>$|', '', $text, 1);  // remove suffix 2
    $text = trim($text);  // remove line breaks
    $text = preg_replace('|^(\\<span style\\="color\\: #[a-fA-F0-9]{0,6}"\\>)(&lt;\\?php&nbsp;)(.*?)(\\</span\\>)|', '$1$3$4', $text);  // remove custom added "<?php "

    return $text;
}

/**
 * Render a svg diagram in the document
 *
 * @param string $code
 *
 * @return string
 */
function diagramSvg($code, $params)
{
    list($type, $height) = explode(' ', $params);
    $style = "height: $height";
    return sprintf('<iframe src="%s" class="w-100 border-0" style="%s"></iframe>', $code, $style);
}
