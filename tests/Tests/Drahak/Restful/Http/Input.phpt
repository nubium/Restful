<?php
namespace Tests\Drahak\Restful\Http;

require_once __DIR__ . '/../../../bootstrap.php';

use Drahak\Restful\Http\Input;
use Mockista\MockInterface;
use Nette;
use Tester;
use Tester\Assert;
use Tests\TestCase;

/**
 * Test: Tests\Drahak\Restful\Http\Input.
 *
 * @testCase Tests\Drahak\Restful\Http\InputTest
 * @author Drahomír Hanák
 * @package Tests\Drahak\Restful\Http
 */
class InputTest extends TestCase
{

	/** @var array */
	private $data;

	/** @var MockInterface */
	private $validationScope;

	/** @var MockInterface */
	private $validationScopeFactory;

	/** @var Input */
	private $input;

    public function setUp()
    {
		parent::setUp();
		$this->data = array('hello_message' => 'Hello World');
		$this->validationScope = $this->mockista->create('Drahak\Restful\Validation\ValidationScope');
		$this->validationScopeFactory = $this->mockista->create('Drahak\Restful\Validation\IValidationScopeFactory');
		$this->input = new Input($this->validationScopeFactory, $this->data);
    }
    
    public function testGetData()
    {
		$data = $this->input->getData();
		Assert::same($data, $this->data);
    }

	public function testGetData_callingPropertyName_shouldReturnNameValueFromData()
	{
		$data = $this->input->setData(['name' => 'John Doe']);
		Assert::equal('John Doe', $this->input->name);
	}

	public function testGetData_callingPropertyData_shouldReturnDataValueFromData()
	{
		$data = $this->input->setData(['data' => 'test data', 'private' => 'private value', 'object' => ['id' => 5, 'name' => 'row']]);
		Assert::equal('test data', $this->input->data);
	}

	public function testGetData_callingPropertyPrivate_shouldReturnPropertyValueFromData()
	{
		$data = $this->input->setData(['data' => 'test data', 'private' => 'private value', 'object' => ['id' => 5, 'name' => 'row']]);
		Assert::equal('private value', $this->input->private);
	}

	public function testGetData_callingPropertyObject_shouldReturnObjectValueFromData()
	{
		$data = $this->input->setData(['data' => 'test data', 'private' => 'private value', 'object' => ['id' => 5, 'name' => 'row']]);
		Assert::equal(['id' => 5, 'name' => 'row'], $this->input->object);
	}

	public function testGetData_callingInvalidProperty_shouldThrowException()
	{
		$data = $this->input->setData(['data' => 'test data', 'private' => 'private value', 'object' => ['id' => 5, 'name' => 'row']]);
		Assert::exception(function() {
			$this->input->unknown;
		}, '\Nette\MemberAccessException', 'Cannot read an undeclared property Drahak\Restful\Http\Input::$unknown.');
	}

	public function testGetValidationField()
	{
		$field = $this->mockista->create('Drahak\Restful\Validation\Field');
		$this->validationScopeFactory->expects('create')
			->once()
			->andReturn($this->validationScope);

		$this->validationScope->expects('field')
			->once()
			->with('name')
			->andReturn($field);

		$result = $this->input->field('name');
		Assert::same($result, $field);
	}

	public function testValidateInputData()
	{
		$errors = array();

		$this->validationScopeFactory->expects('create')
			->once()
			->andReturn($this->validationScope);
		$this->validationScope->expects('validate')
			->once()
			->with($this->data)
			->andReturn($errors);

		$result = $this->input->validate();
		Assert::equal($result, $errors);
	}

	public function testIfInputDataIsValid()
	{
		$errors = array();

		$this->validationScopeFactory->expects('create')
			->once()
			->andReturn($this->validationScope);
		$this->validationScope->expects('validate')
			->once()
			->with($this->data)
			->andReturn($errors);

		$result = $this->input->isValid();
		Assert::true($result);
	}

}
\run(new InputTest());
