# Yay PHP

## How to install

1. <code>composer require paymobi/yay</code>

## How to use

```php
<?php

use Yay\Yay;

$input = json_decode(file_get_contents("php://input"));

$schema = [
  "name" => Yay::item()->required()->string()->minLength(10)->maxLength(20),
  "age" => Yay::item()->required()->integer(),
  "cpf" => Yay::item()->required()->strHasOnlyDigits()->length(11),
  "skillLevel" => Yay::item()->required()->isNumberBetween([1, 5]),
  "weight" => Yay::item()->required()->float(),
  "birthdate" => Yay::item()->required()->isBrazilDateFormat(),
  "parents" => Yay::item()->required()->array()->length(2)->itemsOfType(
    Yay::item()->string()->minLength(10)->maxLength(20)
  ),
  "nickname" => Yay::item()->optional()->string(),
];

$errors = Yay::validate($schema, $input);


if ($errors) {
  echo("request body incorrect");
}

echo("request body correct");
</code>
```
