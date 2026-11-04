<?php

/**
 * Enforces the writing rules of this repository.
 *
 * PHP files must be ASCII only and may not contain line or block comments other than docblocks.
 * Markdown files may not contain emoji, em or en dashes, arrow symbols or bullet lists outside code fences.
 *
 * Usage: php tools/check-style.php [path ...]
 * Without arguments the whole repository is checked. Exit code 1 means violations were found.
 */

$root = dirname(__DIR__);
$paths = array_slice($argv, 1);
if (!$paths) {
    $paths = array('src', 'tests', 'examples', 'tools', '.github', 'README.md', 'CHANGELOG.md');
}

$files = array();
foreach ($paths as $path) {
    $absolute = is_file($path) || is_dir($path) ? $path : $root . DIRECTORY_SEPARATOR . $path;
    if (is_file($absolute)) {
        $files[] = $absolute;
    } elseif (is_dir($absolute)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($absolute, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }
    }
}

$violations = array();
foreach ($files as $file) {
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if ($extension === 'php') {
        $violations = array_merge($violations, check_php($file));
    } elseif ($extension === 'md') {
        $violations = array_merge($violations, check_markdown($file));
    }
}

foreach ($violations as $violation) {
    fwrite(STDERR, $violation . PHP_EOL);
}
if ($violations) {
    fwrite(STDERR, count($violations) . ' style violation(s).' . PHP_EOL);
    exit(1);
}
exit(0);

/**
 * @param string $file
 *
 * @return string[]
 */
function check_php($file)
{
    $violations = array();
    $source = file_get_contents($file);

    if (preg_match_all('/[^\x09\x0A\x0D\x20-\x7E]/', $source, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[0] as $match) {
            $violations[] = sprintf('%s:%d: non-ASCII character', $file, line_of($source, $match[1]));
        }
    }

    foreach (token_get_all($source) as $token) {
        if (!is_array($token) || $token[0] !== T_COMMENT) {
            continue;
        }
        if (strpos($token[1], '#[') === 0) {
            continue;
        }
        $violations[] = sprintf('%s:%d: comment outside a docblock', $file, $token[2]);
    }

    return $violations;
}

/**
 * @param string $file
 *
 * @return string[]
 */
function check_markdown($file)
{
    $rules = array(
        'emoji' => '/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}\x{FE0F}\x{200D}]/u',
        'em or en dash' => '/[\x{2013}\x{2014}\x{2015}]/u',
        'arrow symbol' => '/[\x{2190}-\x{21FF}\x{27F0}-\x{27FF}\x{2900}-\x{297F}]|(?<![-=<])(?:-->|==>|=>|->|<-)(?![-=>])/u',
        'bullet character' => '/[\x{2022}\x{2023}\x{25E6}\x{2043}\x{2219}]/u',
    );

    $violations = array();
    $inFence = false;
    $inFrontMatter = false;
    foreach (preg_split('/\R/', file_get_contents($file)) as $index => $line) {
        $number = $index + 1;
        if ($number === 1 && trim($line) === '---') {
            $inFrontMatter = true;
            continue;
        }
        if ($inFrontMatter) {
            $inFrontMatter = trim($line) !== '---';
            continue;
        }
        if (preg_match('/^\s*(```|~~~)/', $line)) {
            $inFence = !$inFence;
            continue;
        }
        if ($inFence) {
            continue;
        }
        $prose = preg_replace('/`[^`]*`/', '', $line);
        foreach ($rules as $name => $pattern) {
            if (preg_match($pattern, $prose)) {
                $violations[] = sprintf('%s:%d: %s', $file, $number, $name);
            }
        }
        if (preg_match('/^\s*[-*+]\s+\S/', $line)) {
            $violations[] = sprintf('%s:%d: bullet list, write prose or a table instead', $file, $number);
        }
    }

    return $violations;
}

/**
 * @param string $source
 * @param int    $offset
 *
 * @return int
 */
function line_of($source, $offset)
{
    return substr_count($source, "\n", 0, $offset) + 1;
}
