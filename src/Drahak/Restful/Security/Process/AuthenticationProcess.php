<?php
namespace Drahak\Restful\Security\Process;

use Nette\SmartObject;
use Drahak\Restful\Http\IInput;

/**
 * Request AuthenticationProcess template
 * @package Drahak\Restful\Security\Process
 * @author Drahomír Hanák
 */
abstract class AuthenticationProcess
{
	use SmartObject;

	/**
	 * Authenticate process
	 * @param IInput $input
	 * @return bool
	 */
	public function authenticate(IInput $input)
	{
		$this->authRequestData($input);
		$this->authRequestTimeout($input);
		return TRUE;
	}

	/**
	 * Authenticate request data
	 * @param IInput $input
	 * @return bool
	 */
	abstract protected function authRequestData(IInput $input);

	/**
	 * Authenticate request timeout
	 * @param IInput $input
	 * @return bool
	 */
	abstract protected function authRequestTimeout(IInput $input);

}
