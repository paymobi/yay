<?php

namespace Yay;

use DateTime;

class Yay
{
  public static function item(): YayItem
  {
    return new YayItem();
  }

  public static function validate(array $yayItemsSchema, ?object $values, $strict = false): ?array
  {
    if ($values == null)
      $values = (object)[];

    $errors = [];
    foreach ($yayItemsSchema as $key => $yayItem) {
      $hasKey  = isset($values->$key);
      $error = $yayItem->validate($hasKey ? $values->$key : null);
      if ($error) $errors[$key] = $error;
    }

    if ($strict) {
      foreach ($values as $key => $_value) {
        if (!isset($yayItemsSchema[$key])) {
          $errors[$key] = 'is not allowed in the schema';
        }
      }
    }

    return count($errors) == 0 ? null : $errors;
  }
}

class YayItem
{
  private array $yayChecks = [];
  private bool $optional = false;
  private bool $isArray = false;
  private ?array $objectYayItemsSchema;
  private ?int $maxLength;
  private ?int $minLength;
  private ?int $length;
  private ?array $numberBetween;
  private ?YayItem $arrayYayItem;
  private $customValidator;

  public function validate($value): ?string
  {
    if ($value === null && $this->optional) return null;

    foreach ($this->yayChecks as $check) {
      $isValidFunc = $check->isValid;
      $validated = $isValidFunc($value);

      if (is_string($validated)) {
        return $validated;
      } else if (!$validated) {
        return $check->errorMessage;
      }
    }

    return null;
  }

  public function required($message = 'is required'): YayItem
  {
    $this->optional = false;
    $this->_addCheck(new YayCheck($message, function ($value) {
      return $value !== null;
    }));
    return $this;
  }

  public function optional(): YayItem
  {
    $this->optional = true;
    return $this;
  }

  public function string($message = 'needs to be a string'): YayItem
  {
    $this->_addCheck(new YayCheck($message, function ($value) {
      return is_string($value);
    }));
    return $this;
  }

  public function integer($message = 'needs to be an integer'): YayItem
  {
    $this->_addCheck(new YayCheck($message, function ($value) {
      return is_int($value) && !is_string($value);
    }));
    return $this;
  }

  public function float($message = 'needs to be a float'): YayItem
  {
    $this->_addCheck(new YayCheck($message, function ($value) {
      return is_numeric($value) && !is_string($value);
    }));
    return $this;
  }

  public function array($message = 'needs to be an array'): YayItem
  {
    $this->isArray = true;
    $this->_addCheck(new YayCheck($message, function ($value) {
      return is_array($value);
    }));
    return $this;
  }

  public function itemsOfType(YayItem $yayItem): YayItem
  {
    if ($this->isArray) {
      $this->arrayYayItem = $yayItem;
      $this->_addCheck(new YayCheck('', function ($arrayValue) {
        foreach ($arrayValue as $item) {
          $errorMessage = $this->arrayYayItem->validate($item);
          if ($errorMessage != null) return "array items $errorMessage";
        }

        return true;
      }));
    }
    return $this;
  }


  public function minLength(int $min, $message = 'has a min length of '): YayItem
  {
    $this->minLength = $min;
    $this->_addCheck(new YayCheck($message . $min, function ($value) {
      return $this->_calcLength($value) >= $this->minLength;
    }));
    return $this;
  }

  public function length(int $length, $message = 'needs to have a length of '): YayItem
  {
    $this->length = $length;
    $this->_addCheck(new YayCheck($message . $length, function ($value) {
      return $this->_calcLength($value) == $this->length;
    }));
    return $this;
  }

  public function maxLength(int $max, $message = 'has a max length of '): YayItem
  {
    $this->maxLength = $max;
    $this->_addCheck(new YayCheck($message . $max, function ($value) {
      return $this->_calcLength($value) <= $this->maxLength;
    }));
    return $this;
  }

  public function strHasOnlyDigits($message = 'can have only digits'): YayItem
  {
    $this->_addCheck(new YayCheck($message, function ($value) {
      return is_string($value) && ctype_digit($value);
    }));
    return $this;
  }

  public function strIsAlpha($message = 'can have only alpha characters'): YayItem
  {
    $this->_addCheck(new YayCheck($message, function ($value) {
      return ctype_alpha($value);
    }));
    return $this;
  }

  public function bool($message = 'needs to be a boolean value'): YayItem
  {
    $this->_addCheck(new YayCheck($message, function ($value) {
      return is_bool($value);
    }));
    return $this;
  }

  public function strIsUpperAlphaNumeric($message = 'can have only uppercase characters'): YayItem
  {
    $this->_addCheck(new YayCheck($message, function ($value) {
      return is_string($value) && preg_match('/^[A-Z]+[A-Z0-9._]+$/', $value);
    }));
    return $this;
  }

  public function isNumberBetween(array $range, $message = 'needs to be a number between the values'): YayItem
  {
    $this->numberBetween = $range;
    $this->_addCheck(new YayCheck("$message $range[0] and $range[1]", function ($value) {
      return !is_string($value) && $this->numberBetween[0] <= $value && $value <= $this->numberBetween[1];
    }));
    return $this;
  }

  public function isUsDateFormat($message = 'needs to have the date format YYYY-mm-dd'): YayItem
  {
    $this->_addCheck(new YayCheck($message, function ($value) {
      return validateDate($value, 'Y-m-d');
    }));
    return $this;
  }

  public function isBrazilDateFormat($message = 'needs to have the date format dd/mm/YYYY'): YayItem
  {
    $this->_addCheck(new YayCheck($message, function ($value) {
      return validateDate($value, 'd/m/Y');
    }));
    return $this;
  }

  public function object(array $yayItemsSchema, $message = 'needs to be an object'): YayItem
  {
    $this->objectYayItemsSchema = $yayItemsSchema;
    $this->_addCheck(new YayCheck($message, function ($value) {
      $isObject = is_object($value);
      if (!$isObject) return false;

      $errors = Yay::validate($this->objectYayItemsSchema, $value);
      if (!$errors) return true;

      return 'object needs to have schema: ' . json_encode($errors);
    }));
    return $this;
  }

  public function custom($message, callable $validator): YayItem
  {
    $this->customValidator = $validator;
    $this->_addCheck(new YayCheck($message, function ($value) {
      $callback = $this->customValidator;
      return $callback($value);
    }));
    return $this;
  }

  private function _addCheck(YayCheck $check): void
  {
    $this->yayChecks[] = $check;
  }

  private function _calcLength($value)
  {
    return $this->isArray ? count($value) : mb_strlen($value);
  }
}

class YayCheck
{
  public string $errorMessage;
  public $isValid;

  public function __construct(string $errorMessage, callable $isValid)
  {
    $this->errorMessage = $errorMessage;
    $this->isValid = $isValid;
  }
}

function validateDate($date, $format = 'Y-m-d')
{
  $d = DateTime::createFromFormat($format, $date);
  return $d && $d->format($format) === $date;
}