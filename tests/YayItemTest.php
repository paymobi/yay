<?php

use PHPUnit\Framework\TestCase;
use Yay\Yay;

final class YayItemTest extends TestCase
{
  public function testRequired(): void
  {
    $schema = ["name" => Yay::item()->required('is required')];

    $errors = $this->yayValidate($schema, null);
    $this->assertStringContainsString('is required', $errors['name']);

    $errors = $this->yayValidate($schema, []);
    $this->assertStringContainsString('is required', $errors['name']);
  }

  public function testOptional(): void
  {
    $schema = ["name" => Yay::item()->optional()];
    $errors = $this->yayValidate($schema, null);
    $this->assertNull($errors);
  }

  public function testString(): void
  {
    $schema = ["name" => Yay::item()->string()];

    $errors = $this->yayValidate($schema, null);
    $this->assertStringContainsString('string', $errors['name']);

    $errors = $this->yayValidate($schema, array('name' => 1));
    $this->assertStringContainsString('string', $errors['name']);

    $errors = $this->yayValidate($schema, array('name' => []));
    $this->assertStringContainsString('string', $errors['name']);

    $errors = $this->yayValidate($schema, array('name' => 'Márcio'));
    $this->assertNull($errors);
  }

  public function testInteger(): void
  {
    $schema = ["age" => Yay::item()->integer()];

    $errors = $this->yayValidate($schema, null);
    $this->assertStringContainsString('integer', $errors['age']);

    $errors = $this->yayValidate($schema, array('age' => 'string'));
    $this->assertStringContainsString('integer', $errors['age']);

    $errors = $this->yayValidate($schema, array('age' => 12.5));
    $this->assertStringContainsString('integer', $errors['age']);

    $errors = $this->yayValidate($schema, array('age' => 18));
    $this->assertNull($errors);
  }

  public function testFloat(): void
  {
    $schema = ["weight" => Yay::item()->float()];

    $errors = $this->yayValidate($schema, null);
    $this->assertStringContainsString('float', $errors['weight']);

    $errors = $this->yayValidate($schema, array('weight' => 'string'));
    $this->assertStringContainsString('float', $errors['weight']);

    $errors = $this->yayValidate($schema, array('weight' => []));
    $this->assertStringContainsString('float', $errors['weight']);

    $errors = $this->yayValidate($schema, array('weight' => 18));
    $this->assertNull($errors);

    $errors = $this->yayValidate($schema, array('weight' => 18.5));
    $this->assertNull($errors);
  }

  public function testArray(): void
  {
    $schema = ["names" => Yay::item()->array()];
    $errorMessage = 'needs to be an array';

    $errors = $this->yayValidate($schema, null);
    $this->assertStringContainsString($errorMessage, $errors['names']);

    $errors = $this->yayValidate($schema, array('names' => 'Márcio'));
    $this->assertStringContainsString($errorMessage, $errors['names']);

    $errors = $this->yayValidate($schema, array('names' => 12));
    $this->assertStringContainsString($errorMessage, $errors['names']);

    $errors = $this->yayValidate($schema, array('names' => []));
    $this->assertNull($errors);
  }

  public function testItemsOfType(): void
  {
    $schema = ["names" => Yay::item()->array()->itemsOfType(Yay::item()->required()->string())];
    $errors = $this->yayValidate($schema, ['names' => []]);
    $this->assertNull($errors);

    $schema = ["names" => Yay::item()->array()->itemsOfType(Yay::item()->string())];
    $errors = $this->yayValidate($schema, ['names' => [1]]);
    $this->assertStringContainsString('array items needs to be a string', $errors['names']);

    $schema = ["names" => Yay::item()->array()->itemsOfType(Yay::item()->array())];
    $errors = $this->yayValidate($schema, ['names' => ['item']]);
    $this->assertStringContainsString('array items needs to be an array', $errors['names']);

    $schema = ["names" => Yay::item()->array()->itemsOfType(Yay::item()->float())];
    $errors = $this->yayValidate($schema, ['names' => [23.5, '223.3']]);
    $this->assertStringContainsString('array items needs to be a float', $errors['names']);

    $schema = ["names" => Yay::item()->array()->itemsOfType(Yay::item()->float())];
    $errors = $this->yayValidate($schema, ['names' => [23.5, 223.3]]);
    $this->assertNull($errors);

    // array inside array
    $recursiveSchema = [
      "names" => Yay::item()->array()->itemsOfType(
        Yay::item()->array()->minLength(2)->itemsOfType(
          Yay::item()->integer(),
        )
      )
    ];
    $errors = $this->yayValidate($recursiveSchema, ['names' => []]);
    $this->assertNull($errors);

    $errors = $this->yayValidate($recursiveSchema, ['names' => [2, 2]]);
    $this->assertStringContainsString('array items needs to be an array', $errors['names']);

    $errors = $this->yayValidate($recursiveSchema, ['names' => [[2], [2]]]);
    $this->assertStringContainsString('array items has a min length of 2', $errors['names']);

    $errors = $this->yayValidate($recursiveSchema, ['names' => [[2, 2], [2, '3']]]);
    $this->assertStringContainsString('array items array items needs to be an integer', $errors['names']);

    $errors = $this->yayValidate($recursiveSchema, ['names' => [[2, 2], [2, 3]]]);
    $this->assertNull($errors);
  }

  /**
   * @depends testArray
   */
  public function testMinLength(): void
  {
    $schema = [
      "name" => Yay::item()->minLength(5),
      "groups" => Yay::item()->array()->minLength(2),
    ];

    $errors = $this->yayValidate($schema, null);
    $this->assertStringContainsString('min length of 5', $errors['name']);

    $errors = $this->yayValidate($schema, array('name' => 'Ma', 'groups' => []));
    $this->assertStringContainsString('min length of 5', $errors['name']);
    $this->assertStringContainsString('min length of 2', $errors['groups']);

    $errors = $this->yayValidate($schema, array('name' => 'Márcio', 'groups' => ['Márcio', 'Iago']));
    $this->assertNull($errors);
  }

  /**
   * @depends testArray
   */
  public function testLength(): void
  {
    $schema = [
      "name" => Yay::item()->length(6),
      "groups" => Yay::item()->array()->length(2),
    ];
    $strError = 'needs to have a length of 6';
    $arrayError = 'needs to have a length of 2';

    $errors = $this->yayValidate($schema, null);
    $this->assertStringContainsString($strError, $errors['name']);

    $errors = $this->yayValidate($schema, array('name' => 'Márc', 'groups' => ''));
    $this->assertStringContainsString($strError, $errors['name']);

    $errors = $this->yayValidate($schema, array('name' => 'Márcio Gabriel', 'groups' => [1]));
    $this->assertStringContainsString($strError, $errors['name']);
    $this->assertStringContainsString($arrayError, $errors['groups']);

    $errors = $this->yayValidate($schema, array('groups' => [1, 2, 3]));
    $this->assertStringContainsString($arrayError, $errors['groups']);

    $errors = $this->yayValidate($schema, array('name' => 'Márcio', 'groups' => [1, 2]));
    $this->assertNull($errors);
  }

  /**
   * @depends testArray
   */
  public function testMaxLength(): void
  {
    $schema = [
      "name" => Yay::item()->maxLength(4),
      'groups' => Yay::item()->array()->maxLength(2),
    ];

    $errors = $this->yayValidate($schema, null);
    $this->assertArrayNotHasKey('name', $errors);

    $errors = $this->yayValidate($schema, array('name' => 'Márc', 'groups' => [1, 2]));
    $this->assertNull($errors);

    $errors = $this->yayValidate($schema, array('name' => 'Márcio', 'groups' => [1, 2, 3]));
    $this->assertStringContainsString('max length of 4', $errors['name']);
    $this->assertStringContainsString('max length of 2', $errors['groups']);
  }

  public function testStrIsAlpha(): void
  {
    $errorMessage = 'can have only alpha characters';
    $schema = ["uf" => Yay::item()->strIsAlpha()];

    $errors = $this->yayValidate($schema, null);
    $this->assertStringContainsString($errorMessage, $errors['uf']);

    $errors = $this->yayValidate($schema, ['uf' => []]);
    $this->assertStringContainsString($errorMessage, $errors['uf']);

    $errors = $this->yayValidate($schema, ['uf' => '12f']);
    $this->assertStringContainsString($errorMessage, $errors['uf']);

    $errors = $this->yayValidate($schema, ['uf' => 'RJ']);
    $this->assertNull($errors);
  }

  public function testStrIsUpperAlphaNumeric(): void
  {
    $errorMessage = 'can have only uppercase characters';
    $schema = ["name" => Yay::item()->strIsUpperAlphaNumeric()];

    $errors = $this->yayValidate($schema, null);
    $this->assertStringContainsString($errorMessage, $errors['name']);

    $errors = $this->yayValidate($schema, ['name' => []]);
    $this->assertStringContainsString($errorMessage, $errors['name']);

    $errors = $this->yayValidate($schema, ['name' => 'Márcio']);
    $this->assertStringContainsString($errorMessage, $errors['name']);

    $errors = $this->yayValidate($schema, ['name' => 'MÁRCIO 28!']);
    $this->assertStringContainsString($errorMessage, $errors['name']);

    $errors = $this->yayValidate($schema, ['name' => 'RJ2']);
    $this->assertNull($errors);
  }

  public function testStrHasOnlyDigits(): void
  {
    $errorMessage = 'can have only digits';
    $schema = ["cpf" => Yay::item()->strHasOnlyDigits($errorMessage)];

    $errors = $this->yayValidate($schema, null);
    $this->assertStringContainsString($errorMessage, $errors['cpf']);

    $errors = $this->yayValidate($schema, array('cpf' => '222.222.333-90'));
    $this->assertStringContainsString($errorMessage, $errors['cpf']);

    $errors = $this->yayValidate($schema, array('cpf' => 2222));
    $this->assertStringContainsString($errorMessage, $errors['cpf']);

    $errors = $this->yayValidate($schema, array('cpf' => '12345678990'));
    $this->assertNull($errors);
  }

  public function testIsNumberBetween(): void
  {
    $schema = ["level" => Yay::item()->isNumberBetween([-1, 5])];
    $errorMessage = 'be a number between the values';

    $errors = $this->yayValidate($schema, null);
    $this->assertStringContainsString($errorMessage, $errors['level']);

    $errors = $this->yayValidate($schema, array('level' => '222.222.333-90'));
    $this->assertStringContainsString($errorMessage, $errors['level']);

    $errors = $this->yayValidate($schema, array('level' => 2222));
    $this->assertStringContainsString($errorMessage, $errors['level']);

    $errors = $this->yayValidate($schema, array('level' => -2));
    $this->assertStringContainsString($errorMessage, $errors['level']);

    $errors = $this->yayValidate($schema, array('level' => '4'));
    $this->assertStringContainsString($errorMessage, $errors['level']);

    $errors = $this->yayValidate($schema, array('level' => 5));
    $this->assertNull($errors);

    $errors = $this->yayValidate($schema, array('level' => -1));
    $this->assertNull($errors);
  }

  public function testIsUsDateFormat(): void
  {
    $schema = ["date" => Yay::item()->isUsDateFormat()];
    $errorMessage = 'needs to have the date format YYYY-mm-dd';

    $errors = $this->yayValidate($schema, null);
    $this->assertStringContainsString($errorMessage, $errors['date']);

    $errors = $this->yayValidate($schema, array('date' => ''));
    $this->assertStringContainsString($errorMessage, $errors['date']);

    $errors = $this->yayValidate($schema, array('date' => '01/01/2004'));
    $this->assertStringContainsString($errorMessage, $errors['date']);

    $errors = $this->yayValidate($schema, array('date' => '2000-13-19'));
    $this->assertStringContainsString($errorMessage, $errors['date']);

    $errors = $this->yayValidate($schema, array('date' => '2004-01-01'));
    $this->assertNull($errors);
  }

  public function testIsBrazilDateFormat(): void
  {
    $schema = ["date" => Yay::item()->isBrazilDateFormat()];
    $errorMessage = 'needs to have the date format dd/mm/YYYY';

    $errors = $this->yayValidate($schema, null);
    $this->assertStringContainsString($errorMessage, $errors['date']);

    $errors = $this->yayValidate($schema, array('date' => ''));
    $this->assertStringContainsString($errorMessage, $errors['date']);

    $errors = $this->yayValidate($schema, array('date' => '19/13/2000'));
    $this->assertStringContainsString($errorMessage, $errors['date']);

    $errors = $this->yayValidate($schema, array('date' => '2004-01-01'));
    $this->assertStringContainsString($errorMessage, $errors['date']);

    $errors = $this->yayValidate($schema, array('date' => '01/01/2004'));
    $this->assertNull($errors);
  }

  public function testBool(): void
  {
    $schema = ["automatic" => Yay::item()->bool()];
    $errorMessage = 'needs to be a boolean value';

    $errors = $this->yayValidate($schema, null);
    $this->assertStringContainsString($errorMessage, $errors['automatic']);

    $errors = $this->yayValidate($schema, ['automatic' => '']);
    $this->assertStringContainsString($errorMessage, $errors['automatic']);

    $errors = $this->yayValidate($schema, ['automatic' => 1]);
    $this->assertStringContainsString($errorMessage, $errors['automatic']);

    $errors = $this->yayValidate($schema, ['automatic' => []]);
    $this->assertStringContainsString($errorMessage, $errors['automatic']);

    $errors = $this->yayValidate($schema, ['automatic' => true]);
    $this->assertNull($errors);

    $errors = $this->yayValidate($schema, ['automatic' => false]);
    $this->assertNull($errors);
  }

  public function testObject(): void
  {
    $schema = [
      'customer' => Yay::item()->object([
        "name" => Yay::item()->string(),
        "age" => Yay::item()->integer(),
        "nickname" => Yay::item()->optional()->string(),
        "hobbies" => Yay::item()->array()->itemsOfType(Yay::item()->string())->minLength(2),
      ]),
    ];

    $errors = $this->yayValidate($schema, null);
    $this->assertStringContainsString('needs to be an object', $errors['customer']);

    $errors = $this->yayValidate($schema, ['customer' => 'Márcio']);
    $this->assertStringContainsString('needs to be an object', $errors['customer']);

    $errors = $this->yayValidate($schema, ['customer' => array(
      'n' => 'Márcio',
    )]);
    $this->assertStringContainsString('object needs to have schema: ', $errors['customer']);

    $errors = $this->yayValidate($schema, ['customer' => array(
      'name' => 'Márcio',
      'age' => 18,
      'hobbies' => ['Programming', 'Workout']
    )]);
    $this->assertNull($errors);
  }

  public function testStrictMode(): void
  {
    $schema = [
      'name' => Yay::item()->string(),
      'age' => Yay::item()->integer(),
    ];

    $errors = $this->yayValidate($schema, ['name' => 'Márcio', 'age' => 18, 'weight' => 63.5], true);
    $this->assertStringContainsString('is not allowed', $errors['weight']);
  }

  public function testCustom(): void
  {
    $errorMessage = 'not pass in custom validator';
    $schema = ["document_type" => Yay::item()->custom($errorMessage, function ($value) {
      return $value == 'cpf' || $value == 'rg';
    })];

    $errors = $this->yayValidate($schema, null);
    $this->assertStringContainsString($errorMessage, $errors['document_type']);

    $errors = $this->yayValidate($schema, array('document_type' => '222.222.333-90'));
    $this->assertStringContainsString($errorMessage, $errors['document_type']);

    $errors = $this->yayValidate($schema, array('document_type' => 'rgg'));
    $this->assertStringContainsString($errorMessage, $errors['document_type']);

    $errors = $this->yayValidate($schema, array('document_type' => 'rg'));
    $this->assertNull($errors);

    $errors = $this->yayValidate($schema, array('document_type' => 'cpf'));
    $this->assertNull($errors);
  }

  private function yayValidate($schema, $array, $strict = false)
  {
    return Yay::validate($schema, $this->_formatAsObj($array), $strict);
  }

  private function _formatAsObj(?array $array)
  {
    return (object)json_decode(json_encode($array));
  }
}