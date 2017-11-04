<?php
use Icecave\Temptation\Temptation;

class Test extends \PHPUnit_Framework_TestCase {
  /**
   * @dataProvider provide1
   */
  public function test1
    ($PHP_name, array $files, $path, $expected_status_code,
      $expected_content_type, $body_regex)
  {
    #> Given

    $temp_dir = self::createTemporaryDirectory();
    self::makeFiles($temp_dir->path(), $files);
    $PHP = new PhpServer($PHP_name, $temp_dir->path(), self::getRouter());

    #> When

    $response = $this->makeRequest($PHP->getPort(), $path);

    #> Then

    $this->assertSame($expected_status_code, $response->status_code);
    $this->assertRegexp($body_regex, $response->body);

    #> Cleanup

    unset($PHP);
  }

  public function provide1() {
    $tests = [
      [
        ['index.html' => 'This is index.html'],
        '/',
        200,
        'text/html',
        '@This is index\.html@',
      ],
      [
        [
          'index.php' => '<?php echo "This is index.php", PHP_EOL;',
        ],
        '/',
        200,
        'text/html',
        '@This is index\.php@',
      ],
      [
        ['index.xhtml' => 'This is index.xhtml'],
        '/',
        200,
        'application/xhtml+xml',
        '@This is index\.xhtml@',
      ],
      [
        [
          'x.php' => '<?php echo "This is x.php", PHP_EOL;',
        ],
        '/x.php',
        200,
        'text/html',
        '@This is x\.php@',
      ],
      [
        ['prog.c' => '/* A comment */'],
        '/prog.c',
        200,
        'text/x-csrc',
        '@A comment@',
      ],
      [
        [],
        '/',
        404,
        'text/html',
        '@\A@',
      ]
    ];

    $result = [];

    foreach ($tests as $test) {
      foreach (self::PHP_names() as $name) {
        array_push($result, array_merge([$name], $test));
      }
    }

    return $result;
  }

  private static function createTemporaryDirectory() {
    return (new Temptation)->createDirectory();
  }

  private static function getRouter() {
    $path = implode(
      DIRECTORY_SEPARATOR,
      [__DIR__, '..', '..', '..', 'src', 'router.php']
    );
    return realpath($path);
  }

  /**
    * Create files under $path as set out in $files.
    * 
    * Keys in $files must not contain slashes.
    *
    * @return void
    */
  private static function makeFiles($path, array $files) {
    foreach ($files as $name => $content) {
      $path2 = $path . DIRECTORY_SEPARATOR . $name;
      file_put_contents($path2, $content);
    }
  }

  private static function PHP_names() {
    return preg_split('@\s+@', PHP_NAMES, -1, PREG_SPLIT_NO_EMPTY);
    #< PHP_NAMES is expected to come from PHPUnit's configuration
  }

  private function makeRequest($port, $path) {
    $URL = 'http://' . TEST_HTTP_HOST . ':' . $port . $path;

    $curl = curl_init($URL);

    curl_setopt_array(
      $curl,
      [
        CURLOPT_RETURNTRANSFER => TRUE,
      ]
    );

    $curl_result = curl_exec($curl);

    if ($curl_result === FALSE) {
      $result = NULL;
    } else {
      $result = (object) [
        'status_code' => curl_getinfo($curl, CURLINFO_HTTP_CODE),
        'content_type' => curl_getinfo($curl, CURLINFO_CONTENT_TYPE),
        'body' => $curl_result,
      ];
    }

    curl_close($curl);

    return $result;
  }
}
