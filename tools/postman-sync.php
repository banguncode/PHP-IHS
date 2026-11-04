<?php

/**
 * Derives repository artifacts from exported SATUSEHAT Postman collections.
 *
 * Usage:
 *   php tools/postman-sync.php errors <dir>    Writes tests/Fixtures/errors from the "00." collection.
 *   php tools/postman-sync.php tree <file>     Prints the folder and request tree of one collection.
 *
 * <dir> holds collection JSON files as returned by GET https://api.getpostman.com/collections/{uid}
 * or exported from the Postman app. Only the 00 collection is read, for its error responses.
 */

$root = dirname(__DIR__);
$command = isset($argv[1]) ? $argv[1] : null;
$target = isset($argv[2]) ? $argv[2] : null;

if (!in_array($command, array('errors', 'tree'), true) || $target === null) {
    fwrite(STDERR, "Usage: php tools/postman-sync.php errors <dir> or tree <file>\n");
    exit(1);
}

if ($command === 'tree') {
    $collection = load_collection($target);
    echo $collection['info']['name'], PHP_EOL;
    print_tree($collection['item'], 1);
    exit(0);
}

$collections = array();
foreach (glob(rtrim($target, '/\\') . '/*.json') as $file) {
    $collection = load_collection($file);
    if ($collection !== null) {
        $collections[] = $collection;
    }
}
if (!$collections) {
    fwrite(STDERR, "No Postman collections found in $target\n");
    exit(1);
}

$written = write_error_fixtures($collections, $root . '/tests/Fixtures/errors');
echo "Wrote $written fixture(s)\n";
exit(0);

/**
 * @param string $file
 *
 * @return array|null
 */
function load_collection($file)
{
    $data = json_decode(file_get_contents($file), true);
    if (isset($data['collection'])) {
        $data = $data['collection'];
    }

    return isset($data['info']['name'], $data['item']) ? $data : null;
}

/**
 * @param array $items
 * @param int   $depth
 */
function print_tree(array $items, $depth)
{
    foreach ($items as $item) {
        $indent = str_repeat('  ', $depth);
        if (isset($item['item'])) {
            echo $indent, '[', $item['name'], ']', PHP_EOL;
            print_tree($item['item'], $depth + 1);
            continue;
        }
        $responses = isset($item['response']) ? count($item['response']) : 0;
        echo $indent, $item['request']['method'], ' ', request_url($item['request']), '  (', $item['name'], ', ', $responses, ' example)', PHP_EOL;
    }
}

/**
 * @param array $request
 *
 * @return string
 */
function request_url(array $request)
{
    return is_array($request['url']) ? (isset($request['url']['raw']) ? $request['url']['raw'] : '') : (string) $request['url'];
}

/**
 * @param array    $items
 * @param callable $visit Receives each request item.
 */
function walk_requests(array $items, $visit)
{
    foreach ($items as $item) {
        if (isset($item['item'])) {
            walk_requests($item['item'], $visit);
        } elseif (isset($item['request'])) {
            call_user_func($visit, $item);
        }
    }
}

/**
 * @param array[] $collections
 * @param string  $directory
 *
 * @return int
 */
function write_error_fixtures(array $collections, $directory)
{
    $names = array(
        'Error 200 - Consent Error' => 'consent-suppressed',
        'Error 400 - Path URL & Payload ID tidak sesuai' => 'resource-id-mismatch',
        'Error 400 - Belum OPTIN' => 'not-opted-in',
        'Error 400 - Error JSON' => 'unparseable-resource',
        'Error 401 - Error no API role' => 'no-api-product',
        'Error 401 - Invalid ClientID' => 'invalid-client',
        'Error 401 - Invalid Token' => 'invalid-token',
        'Error 403 - Forbidden' => 'forbidden-html',
        'Error 404 - Wrong URL' => 'wrong-url',
        'Error 503 - API error' => 'no-healthy-upstream',
        'Immunization : Imunisasi Dasar' => 'token-expired',
        'Create Failed - Tidak Sesuai' => 'patient-data-mismatch',
        'Create Failed - Patient Duplicate' => 'patient-duplicate',
        'Patient - Create New Born Exists' => 'newborn-duplicate',
        'Patient - Create New Born Ibu Not Found' => 'mother-not-found',
        'EpisodeOfCare - Update' => 'reference-not-found',
    );

    if (!is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    $written = 0;
    foreach ($collections as $collection) {
        if (strpos($collection['info']['name'], '00.') !== 0) {
            continue;
        }
        walk_requests($collection['item'], function ($item) use ($names, $directory, &$written) {
            foreach (isset($item['response']) ? $item['response'] : array() as $response) {
                if (!isset($names[$response['name']], $response['code'])) {
                    continue;
                }
                $contentType = '';
                foreach (isset($response['header']) ? (array) $response['header'] : array() as $header) {
                    if (strtolower($header['key']) === 'content-type') {
                        $contentType = $header['value'];
                    }
                }
                $fixture = array('status' => $response['code'], 'content_type' => $contentType, 'body' => (string) $response['body']);
                file_put_contents(
                    $directory . '/' . $names[$response['name']] . '.json',
                    json_encode($fixture, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
                );
                $written++;
            }
        });
    }

    return $written;
}
