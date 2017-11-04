<?php
namespace CLI_Router;

if (!(new Router)->sendPageOrReturnFalse()) {
  return FALSE;
}

class Router {
  /** @return bool TRUE iff this function sent a file */
  public function sendPageOrReturnFalse() {
    $p = $_SERVER['DOCUMENT_ROOT'] . $_SERVER['PHP_SELF'];
    if (is_dir($p)) {
      $tmp = $p . DIRECTORY_SEPARATOR . 'index.xhtml';
      if (!file_exists($tmp)) {
        return FALSE;
      } else {
        $p = $tmp;
      }
    }
    return is_readable($p)? $this->sendFileOrReturnFalse($p): FALSE;
  }

  /** @return string[] */
  private static function extensionsKnownToPhp() {
    /*>
      Copy-and-pasted direct from
      <https://secure.php.net/manual/en/features.commandline.webserver.php>.
    */
    $list = '
      .3gp, .apk, .avi, .bmp, .css, .csv, .doc, .docx, .flac, .gif, .gz, .gzip, .htm, .html, .ics, .jpe, .jpeg, .jpg, .js, .kml, .kmz, .m4a, .mov, .mp3, .mp4, .mpeg, .mpg, .odp, .ods, .odt, .oga, .ogg, .ogv, .pdf, .pdf, .png, .pps, .pptx, .qt, .svg, .swf, .tar, .text, .tif, .txt, .wav, .webm, .wmv, .xls, .xlsx, .xml, .xsl, .xsd, and .zip.
    ';

    $tmp = $list;
    $tmp = strtolower($tmp);
    $tmp = rtrim(" \t\n.", $tmp);
    $tmp = preg_split('@[\s,]+@', $tmp, -1, PREG_SPLIT_NO_EMPTY);
    $tmp = array_diff($tmp, ['and']);
    $tmp = preg_replace('@\A\.@', '', $tmp);
    $result = $tmp;

    return $tmp;
  }

  /** @return bool */
  private static function isExtensionKnownToPhp($extension) {
    return in_array(strtolower($extension), self::extensionsKnownToPhp());
  }

  /** @return null|string */
  private function determineContentType($extension) {
    static $map = NULL;

    if ($map === NULL) {
      $map = $this->getMap();
    }

    if (array_key_exists($extension, $map)) {
      return $map[$extension];
    }
  }

  /** @return array */
  private function getMap() {
    $file = self::mimeTypesFile();

    $result = [];

    if (is_file($file)) {
      foreach (file($file, FILE_IGNORE_NEW_LINES) as $line) {
        $words = preg_split('@\s+@', $line, -1, PREG_SPLIT_NO_EMPTY);
        if (count($words) >= 2 && $words[0]{0} !== '#') {
          $content_type = $words[0];
          $extensions = array_slice($words, 1);
          foreach ($extensions as $e) {
            $result[$e] = $content_type;
          }
        }
      }
    }

    return $result;
  }

  private function mimeTypesFile() {
    $candidates = [
      '/etc/mime.types',
      implode(
        DIRECTORY_SEPARATOR,
        [__DIR__, '..', 'data', 'mime.types']
      )
    ];

    foreach ($candidates as $f) {
      if (is_file($f) && is_readable($f)) {
        return $f;
      }
    }

    throw new \Exception("cannot find mime types file");
  }

  /** @return bool TRUE iff this function sent a file */
  private function sendFileOrReturnFalse($path) {
    $extension = pathinfo($path, PATHINFO_EXTENSION);
    if ($extension === '' || self::isExtensionKnownToPhp($extension)) {
      return FALSE;
    } else {
      $content_type = $this->determineContentType($extension);
      if ($content_type === NULL) {
        return FALSE;
      } else {
        header('Content-Type: ' . $content_type);
        readfile($path);
        return TRUE;
      }
    }
  }
}
