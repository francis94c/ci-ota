<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once('Builder.php');

class OTA
{
  /**
   * [BUILD_DIR description]
   * @var [type]
   */
  const BUILD_DIR = FCPATH.'ota_build/';

  /**
   * Secret signing key.
   * @var string
   */
  private $secret;

  /**
   * Hash_mac signing algorithm.
   * @var string
   */
  private $algorithm;
  private $excludes = [];
  private $descriptor;

  /**
   * [__construct Constructor.]
   * @date  2020-01-03
   * @param array $params Initialization Array.
   */
  function __construct(?array $params=null)
  {
    if ($params != null) {
      $this->secret = $params['secret'] ?? 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
      $this->algorithm = $params['algorithm'] ?? 'sha256';
      $this->excludes = $params['excludes'] ?? [];
    }

    if (is_file(FCPATH.'splint.json')) {
      $this->descriptor = json_decode(file_get_contents(FCPATH.'splint.json'));
      if ($this->descriptor == null) {
        $this->descriptor = new stdClass();
      }
    } else {
      $this->descriptor = new stdClass();
    }

    for ($x = 0; $x < count($this->excludes); $x++) {
      $this->excludes[$x] = str_replace('*', '.+');
    }

    $this->excludes = array_merge($this->excludes, [
      '\..+',
      'readme.md'
    ]);
  }

  /**
   * [build description]
   * @date   2020-01-21
   * @return bool       [description]
   */
  public function build():bool
  {
    $this->prepare_build_directory();

    chdir(FCPATH);

    $newSha = $this->get_current_sha();

    $oldSha = $this->get_old_sha();

    if (!$oldSha) {
      $oldSha = $this->get_first_sha();
      $newSha = '';
    }

    $changelist = $this->get_change_list($oldSha, $newSha);

    foreach ($this->excludes as $exclude) {
      $changelist = preg_grep("/^$exclude/i", $changelist, PREG_GREP_INVERT);
    }

    // Build.
    if (!$this->build_patch($changelist)) return false;

    // Save Current Sha.
    $ota = new stdClass();
    $ota->version_sha = $this->get_current_sha();
    $this->descriptor->ota = $ota;

    file_put_contents(FCPATH.'splint.json', json_encode($this->descriptor, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));

    return true;
  }

  /**
   * [build_patch description]
   * @date   2020-01-21
   * @param  array      $changelist [description]
   * @return bool                   [description]
   */
  private function build_patch(array $changelist):bool
  {
    $manifest = [
      'manifest' => []
    ];

    $zip = new ZipArchive();

    if ($zip->open(self::BUILD_DIR.'ota_patch.zip', ZipArchive::CREATE) === true) {
      foreach ($changelist as $file) {
        $this->print("Zipping $file ...");
        $zip->addFile($file, basename($file));
        $manifest['manifest'][basename($file)] = $file;
      }

      $this->print('Writing Manifest...');

      file_put_contents(self::BUILD_DIR.'manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));

      $zip->addFile(self::BUILD_DIR.'manifest.json', 'manifest.json');

      $zip->close();

      $this->print("Done!");
      $this->print('Output: '.self::BUILD_DIR.'ota_patch.zip');

      return true;
    }

    return false;
  }

  /**
   * [print description]
   * @date  2020-01-21
   * @param string     $data [description]
   */
  private function print(string $data):void
  {
    echo $data.PHP_EOL;
  }

  /**
   * [get_change_list description]
   * @date   2020-01-20
   * @param  string     $oldSha [description]
   * @param  ?string    $newSha [description]
   * @return array              [description]
   */
  private function get_change_list(string $oldSha, ?string $newSha):array
  {
    exec("git diff --name-only $oldSha $newSha", $output, $code);

    if ($code != 0) return [];

    return $output;
  }

  /**
   * [get_first_sha description]
   * @date   2020-01-20
   * @return string     [description]
   */
  private function get_first_sha():string
  {
    exec('git rev-list --max-parents=0 HEAD', $output, $code);

    if ($code != 0) return '';

    return substr($output[0], 0, 7);
  }

  /**
   * [prepare_build_directory description]
   * @date 2020-01-03
   */
  private function prepare_build_directory():void
  {
    if (is_dir(self::BUILD_DIR)) {
      get_instance()->load->helper('file');
      delete_files(self::BUILD_DIR);
      rmdir(self::BUILD_DIR);
    }

    mkdir(self::BUILD_DIR);
  }

  /**
   * [get_current_sha description]
   * @date   2020-01-03
   * @return [type]     [description]
   */
  private function get_current_sha():string
  {
    exec('git rev-parse HEAD', $output, $code);

    if ($code != 0) return '';

    return substr($output[0], 0, 7);
  }

  /**
   * [get_old_sha description]
   * @date   2020-01-03
   * @return [type]     [description]
   */
  private function get_old_sha():?string
  {
    return $this->descriptor->ota->version_sha ?? null;
  }
}
