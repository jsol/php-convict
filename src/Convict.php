<?php
namespace Convict;

class Convict {

  private $config = [];
  private $scheme;

  private $fromFile = [];
  private $env = [];
  private $validators = [];

  public function __construct($scheme, $options = [])
  {
    $this->options = $options;

    if ($scheme[0] != '{' && false === strpos($scheme, "\n") && file_exists($scheme)) {
      $scheme = file_get_contents($scheme);
    }

    $this->scheme = json_decode($scheme, true);
    if (null == $this->scheme) {
      throw new \Exception('Invalid config scheme');
    }
    $this->prepareArgsAndEnvs();
    $this->printHelp();
    $this->loadFromScheme($this->scheme);

  }

  public function addFormat(Validator\Validator $v)
  {
    $this->validators[] = $v;
  }

  public function validate()
  {
    $validate = function ($scheme, $parent = '') use (&$validate) {
      foreach ($scheme as $id => $data) {
        $key = trim(sprintf('%s.%s', $parent, $id), '.');
        if (!is_array($data)) {
          continue;
        }

        if (!isset($data['format'])) {
          $validate($data, $key);
          continue;
        }
        $format = $data['format'];
        $val = $this->get($key);

        if (is_array($format)) {
          if (!in_array($val, $format)) {
            throw new \Exception($key . ': ' . $val . ' is not one of ' . implode(', ', $format));
          }
          $format = 'Any';
        }

        $format = ucfirst(strtolower($format));

        if ($format == '*') {
          $format = 'Any';
        }

        if ($val == null) {
          throw new \Exception('Value not registered for ' . $key);
        }

        if (!isset($this->validators[$format])) {
          try {
            $class = '\\Convict\\Validator\\' . $format;
            $this->validators[$format] = new $class();
          } catch (\Exception $e) {
            throw new \Exception('Invalid format: ' . $format);
          }
        }
        $this->validators[$format]->validate($key, $val);
        $this->set($key, $this->validators[$format]->coerce($val));
      }
    };
    set_exception_handler(function(\Exception $e) {
      echo "Error during validation of the parameters: \n";
      echo $e->getMessage() . "\n";
    });

    $validate($this->scheme);
    restore_exception_handler();
  }

  public function writeConfig($filename)
  {
    $write = function ($conf, $scheme, $indent = '  ') use (&$write) {
      $ret = "{\n";
      $last = end($conf);
      foreach ($conf as $id => $value) {
        if (is_array($value)) {
          $ret .= "$indent\"$id\": ";
          $ret .= $write($value, $scheme[$id], $indent . '  ');
          if ($last == $value) {
            $ret = rtrim($ret, ",\n") . "\n";
          }
          continue;
        }

        $ret .= wordwrap("\n" . $indent . '// ' . $scheme[$id]['doc'] . "\n", 75, "\n$indent// ");
        $ret .= $indent . '"' . $id . '": ' . json_encode($value);
        if ($last == $value) {
          $ret .= "\n";
        } else {
          $ret .= ",\n";
        }
      }
      $ret .= substr($indent, 0, -2);
      $ret .= "}";
      if (strlen($indent) > 3) {
        $ret .= ",\n";
      } else {
        $ret .= "\n";
      }
      return $ret;
    };

    $conf = '';
    if (isset($this->scheme['about'])) {
       $conf .= wordwrap("/*\n* " . $this->scheme['about'], 75, "\n* ") . "\n*/\n\n";
    }
    file_put_contents($filename, $conf . $write($this->config, $this->scheme));
  }

  private function printHelp()
  {
    if (isset($this->options['nohelp'])) {
      return;
    }
    if (!isset($this->args['h']) && !isset($this->args['help'])) {
      return;
    }
    $args = function ($data) use (&$args) {
      static $a = [];
      if (is_array($data)) {
        $recurse = true;
        $b = [ 'name' => '' ];
        if (isset($data['arg'])) {
          $b['name'] = '--' . trim($data['arg'], ':');
          $recurse = false;
        }
        if (isset($data['shortarg'])) {
          if (false == $recurse) {
            $b['name'] .= ', ';
          }
          $b['name'] .= '-' . trim($data['shortarg'], ':');
          $recurse = false;
        }
        if ($recurse) {
          foreach ($data as $d) {
            $args($d);
          }
        } else {
          $b['desc'] = $data['doc'];
          if (isset($data['default'])) {
            $b['desc'] .= " (" . $data['default'] . ")";
          } else {
            $b['desc'] .= " (mandatory)";
          }
          $a[] = $b;
        }
      }
      return $a;
    };
    $envs = function ($data) use (&$envs) {
      static $a = [];
      if (is_array($data)) {
        $recurse = true;
        $b = [];
        if (isset($data['env'])) {
          $b['name'] = $data['env'];
          $recurse = false;
        }
        if ($recurse) {
          foreach ($data as $d) {
            $envs($d);
          }
        } else {
          $b['desc'] = $data['doc'];

          if (isset($data['default'])) {
            $b['desc'] .=" (" . $data['default'] . ")";
          } else {
            $b['desc'] .= " (mandatory)";
          }
          $a[] = $b;
        }
      }
      return $a;
    };

    $print = function ($data) {
      $indent = max(array_map(function($l) { return strlen($l['name']); }, $data));
      foreach ($data as $d) {
        echo str_pad($d['name'], $indent) . ' ';
        echo wordwrap($d['desc'], 75, str_pad("\n", $indent + 2, ' ', STR_PAD_RIGHT));
        echo "\n\n";
      }

    };

    if (isset($this->scheme['about'])) {
      echo wordwrap($this->scheme['about'], 75) . "\n\n";
    }

    echo "These are the imputs to this program. The rules of precedence are:
      1. Command line argument
      2. Environment variable
      3. Configuration file value
      4. Default value\n\n";

    echo "Command line arguments:\n";
    $print($args($this->scheme));
    echo "\nEnvironment variables:\n";
    $print($envs($this->scheme));

    die();
  }

  private function prepareArgsAndEnvs()
  {
    $longargs = [];
    $shortargs = [];
    $list = function ($data) use (&$list, &$longargs, &$shortargs) {
      if (is_array($data)) {
        $recurse = true;
        if (isset($data['arg'])) {
          if (substr($data['arg'], -1) != ':') {
            $data['arg'] .= ':';
          }
          $longargs[] = $data['arg'];
          $recurse = false;
        }
        if (isset($data['shortarg'])) {
          if (!substr($data['shortarg'], -1) != ':') {
            $data['shortarg'] .= ':';
          }
          $shortargs[] = $data['shortarg'];
          $recurse = false;
        }
        if (isset($data['env']) && false !== getenv($data['env'], true)) {
          $this->env[$data['env']] = getenv($data['env'], true);
        }
        if ($recurse) {
          foreach ($data as $d) {
            $list($d);
          }
        }
      }
    };
    $list($this->scheme);

    if (!isset($this->options['nohelp'])) {
      $shortargs[] = 'h';
      $longargs[] = 'help';
    }
    $this->args = getopt(implode($shortargs), $longargs);
  }

  public function loadFile($file)
  {
    $json = file_get_contents($file);
    $this->loadConfigJson($json);
  }

  public function loadConfigJson($json)
  {
    $json = preg_replace('|^[ \t]*//(.*)\n|m', '', $json);
    $json = preg_replace('|/\*(.*)\*/|s', '', $json);
    $data = json_decode(trim($json), true);

    if (null == $data) {
      throw new \Exception('Invalid config');
    }
    $set = function ($data, $parent = '') use (&$set) {
      foreach ($data as $id => $d) {
        $key = trim(sprintf('%s.%s', $parent, $id), '.');
        if (is_array($d)) {
          $set($d, $key);
          continue;
        }
        $this->fromFile[$key] = $d;
      }
    };
    $set($data);
    $this->loadFromScheme($this->scheme);
  }

  public function loadFromScheme($scheme, $parent = '')
  {

    $set = function ($scheme, $schemeKey, $fromArray, $configKey) {
      if (isset($scheme[$schemeKey]) && isset($fromArray[trim($scheme[$schemeKey], ':')])) {
        $this->set($configKey, $fromArray[trim($scheme[$schemeKey], ':')]);
        return true;
      }
      return false;
    };
    foreach ($scheme as $id => $data) {
      $c = trim(sprintf('%s.%s', $parent, $id), '.');
      if (isset($data['doc'])) {
        if ($set($data, 'arg', $this->args, $c)) {
          continue;
        }
        if ($set($data, 'shortarg', $this->args, $c)) {
          continue;
        }
        if ($set($data, 'env', $this->env, $c)) {
          continue;
        }
        if (isset($this->fromFile[$c])) {
          $this->set($c, $this->fromFile[$c]);
          continue;
        }
        if ( isset($data['default'])) {
          $this->set($c, $data['default']);
        }
      } else {
        if (is_array($data)) {
          $this->loadFromScheme($data, $c);
        }
      }
    }
  }

  public function set($key, $value)
  {
    $key = trim($key, '.');
    $path = explode('.', $key);
    $current = &$this->config;
    foreach ($path as $p) {
      if (!isset($current[$p]) || !is_array($current[$p])) {
        $current[$p] = [];
      }
      $current = &$current[$p];
    }
    $current = $value;

  }

  public function get($key = '')
  {
    $key = trim($key, '.');
    if (empty($key)) {
      return $this->config;
    }
    $path = explode('.', $key);
    $current = $this->config;
    foreach ($path as $p) {
      if (!isset($current[$p])) {
        return null;
      }
      $current = $current[$p];
    }
    return $current;
  }
}
