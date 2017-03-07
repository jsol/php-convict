<?php
namespace Convict\Validator;

class Size implements Validator {

  public function __construct() {
   $this->units = [
    [["b", "bits"], 1/8],
    [["B", "Bytes"], 1],
    [["Kb"], 128],
    [["k", "K", "kb", "KB", "KiB", "Ki", "ki"], 1024],
    [["Mb"], 131072],
    [["m", "M", "mb", "MB", "MiB", "Mi", "mi"], pow(1024, 2)],
    [["Gb"], 1.342e+8],
    [["g", "G", "gb", "GB", "GiB", "Gi", "gi"], pow(1024, 3)],
    [["Tb"], 1.374e+11],
    [["t", "T", "tb", "TB", "TiB", "Ti", "ti"], pow(1024, 4)],
    [["Pb"], 1.407e+14],
    [["p", "P", "pb", "PB", "PiB", "Pi", "pi"], pow(1024, 5)],
    [["Eb"], 1.441e+17],
    [["e", "E", "eb", "EB", "EiB", "Ei", "ei"], pow(1024, 6)]
  ];

  }

  public function validate($key, $value)
  {
    if (is_numeric($value) && round($value) == $value && $value > 0) {
      return ;
    }
    if (preg_match('/^([0-9\.,]*)(?:\s*)?(.*)$/', $value, $matches)) {
      foreach ($this->units as $unit) {
        if (in_array($matches[2], $unit[0])) {
          return;
        }
      }
    }

    throw new ValidationException($key, $value, $this);
  }

  public function coerce($value)
  {
    if (preg_match('/^([0-9\.,]*)(?:\s*)?(.*)$/', $value, $matches)) {
      $matches[1] = str_replace(',', '.', ''. $matches[1]);
      foreach ($this->units as $unit) {
        if (in_array($matches[2], $unit[0])) {
          return round($matches[1] * $unit[1]);
        }
      }
    }
    return $value;
  }
}
