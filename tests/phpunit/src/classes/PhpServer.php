<?php
class PhpServer {
  public function __construct($PHP_name, $document_root, $router) {
    $this->port = self::findFreePort();

    $cmd = sprintf(
      'exec %s -S %s:%s -t %s %s',
      $PHP_name,
      TEST_HTTP_HOST,
      $this->port,
      $document_root,
      $router
    );
    $dummy = NULL;
    $this->proc = proc_open(
      $cmd,
      [
        ['file', '/dev/null', 'r'],
        ['file', '/dev/null', 'w'],
        ['file', '/dev/null', 'w'],
      ],
      $dummy
    );

    self::waitTillServerReady($this->port);
  }

  public function __destruct() {
    proc_terminate($this->proc);
    proc_close($this->proc);
  }

  /** @return int */
  public function getPort() {
    return $this->port;
  }

  /**
    * @return int
    * @throws \Exception
    */
  private static function findFreePort() {
    $max_port = intval(pow(2, 16) - 1);

    $factory = new \Socket\Raw\Factory;

    for ($port = 4000; $port <= $max_port; $port++) {
      $exception = NULL;
      try {
        $socket = $factory->createClient(TEST_HTTP_HOST . ':'. $port);
      } catch (\Exception $exception) {
        return $port;
      }
      $socket->close();
    }

    throw new \Exception("can't find free port");
  }

  private static function waitTillServerReady($port) {
    $factory = new \Socket\Raw\Factory;

    while (TRUE) {
      $exception = NULL;
      try {
        $socket = $factory->createClient(TEST_HTTP_HOST . ':'. $port);
      } catch (\Exception $exception) {}
      if ($exception === NULL) {
        $socket->close();
        break;
      } else {
        usleep(0.05 * 1e6);
      }
    }

    return TRUE;
  }

  private $port;
  private $proc;
}
