<?php
namespace Drahak\Restful\Application\Responses;

use Nette\Application\IResponse;
use Nette\SmartObject;
use Nette\Http;

/**
 * NullResponse
 * @package Drahak\Restful\Responses
 * @author Drahomír Hanák
 */
class NullResponse implements IResponse
{
	use SmartObject;

	/**
	 * Do nothing
	 * @param Http\IRequest $httpRequest
	 * @param Http\IResponse $httpResponse
	 */
	public function send(Http\IRequest $httpRequest, Http\IResponse $httpResponse)
	{
	}


}
