<?php
namespace Drahak\Restful\Mapping;

use Drahak\Restful\Resource\Media;
use Nette\SmartObject;
use Latte\Runtime\Filters;
use Nette\Utils\Strings;
use Drahak\Restful\InvalidArgumentException;

if ( !class_exists('Latte\Runtime\Filters')) {
	class_alias('Nette\Templating\Helpers', 'Latte\Runtime\Filters');
}

/**
 * DataUrlMapper - encode or decode base64 file
 * @package Drahak\Restful\Mapping
 * @author Drahomír Hanák
 */
class DataUrlMapper implements IMapper
{
	use SmartObject;

	/**
	 * Create DATA URL from file
	 * @param Media $data
	 * @param bool $prettyPrint
	 * @return string
	 *
	 * @throws InvalidArgumentException
	 */
	public function stringify($data, $prettyPrint = TRUE)
	{
		if (!$data instanceof Media) {
			throw new InvalidArgumentException(
				'DataUrlMapper expects object of type Media, ' . (gettype($data)) . ' given'
			);
		}
		return Filters::dataStream((string)$data, $data->getContentType());
	}

	/**
	 * Convert client request data to array or traversable
	 * @param string $data
	 * @return Media
	 *
	 * @throws MappingException
	 */
	public function parse($data)
	{
		$matches = Strings::match($data, "@^data:([\w/]+?);(\w+?),(.*)$@si");
		if (!$matches) {
			throw new MappingException('Given data URL is invalid.');
		}

		return new Media(base64_decode($matches[3]), $matches[1]);
	}

}
