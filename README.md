Nette REST API
==============
This repository is being developed.

### Content
- [Requirements](#requirements)
- [Installation & setup](#installation--setup)
- [Neon configuration](#neon-configuration)
- [Sample usage](#sample-usage)
- [Simple CRUD resources](#simple-crud-resources)
- [Accessing input data](#accessing-input-data)
- [Input data validation](#input-data-validation)
- [Error presenter](#error-presenter)
- [Security & authentication](#security--authentication)
- [Secure your resources with OAuth2](#secure-your-resources-with-oauth2)
- [JSONP support](#jsonp-support)
- [Utilities that make life better](#utilities-that-make-life-better)

Requirements
------------
Drahak/Restful requires PHP version 5.3.0 or higher. The only production dependency is [Nette framework 2.0.x](http://www.nette.org). The Restful also works well with my Drahak\OAuth2 provider (see [Secure your resources with OAuth2](#secure-your-resources-with-oauth2))

Installation & setup
--------------------
The easiest way is to use [Composer](http://doc.nette.org/en/composer)

	$ composer require drahak/restful:@dev

Then add following code to your app bootstrap file before creating container:

```php
Drahak\Restful\DI\RestfulExtension::install($configurator);
```

Neon configuration
------------------
You can configure Drahak\Restful library in config.neon in section `restful`:

```yaml
restful:
	convention: 'snake_case'
	cacheDir: '%tempDir%/cache'
	jsonpKey: 'jsonp'
	prettyPrintKey: 'pretty'
	routes:
		prefix: resources
		module: 'RestApi'
		autoGenerated: TRUE
		panel: TRUE
	security:
		privateKey: 'my-secret-api-key'
		requestTimeKey: 'timestamp'
		requestTimeout: 300
```

- `cacheDir`: not much to say, just directory where to store cache
- `jsonpKey`: sets query parameter name, which enables [JSONP envelope mode](#jsonp-support). Set this to FALSE if you wish to disable it.
- `prettyPrintKey`: API prints every resource with pretty print by default. You can use this query parameter to disable it
- `convention`: resource array keys conventions. Currently supported 3 values: `snake_case`, `camelCase` & `PascalCase` which automatically converts resource array keys. You can write your own converter. Just implement `Drahak\Restful\Resource\IConverter` interface and tag your service with `restful.converter`.
- `routes.prefix`: mask prefix to resource routes (**only for auto generated routes**)
- `routes.module`: default module to resource routes (**only for auto generated routes**)
- `routes.autoGenerated`: if `TRUE` the library auto generate resource routes from Presenter action method annotations (see below)
- `routes.panel`: if `TRUE` the resource routes panel will appear in your nette debug bar
- `security.privateKey`: private key to hash secured requests
- `security.requestTimeKey`: key in request body, where to find request timestamp (see below - [Security & authentication](#security--authentication))
- `security.requestTimeout`: maximal request timestamp age

#### Resource routes panel
It is enabled by default but you can disable it by setting `restful.routes.panel` to `FALSE`. This panel show you all REST API resources routes (exactly all routes in default route list which implements `IResourceRouter` interface). This is useful e.g. for developers who develop client application, so they have all API resource routes in one place.
![REST API resource routes panel](http://files.drahak.eu/restful-routes-panel.png "REST API resource routes panel")

Sample usage
------------
```php
<?php
namespace ResourcesModule;

use Drahak\Restful\IResource;
use Drahak\Restful\Application\UI\ResourcePresenter

/**
 * SamplePresenter resource
 * @package ResourcesModule
 * @author Drahomír Hanák
 */
class SamplePresenter extends ResourcePresenter
{

   protected $typeMap = array(
       'json' => IResource::JSON,
       'xml' => IResource::XML
   );

   /**
    * @GET sample[.<type xml|json>]
    */
   public function actionContent($type = 'json')
   {
       $this->resource->title = 'REST API';
       $this->resource->subtitle = '';
       $this->sendResource($this->typeMap[$type]);
   }

   /**
    * @GET sample/detail
    */
   public function actionDetail()
   {
       $this->resource->message = 'Hello world';
   }

}
```

Resource output is determined by `Accept` header. Library checks the header for `application/xml`, `application/json`, `application/x-data-url` and `application/www-form-urlencoded` and keep an order in `Accept` header.

**Note**: If you call `$presenter->sendResource()` method with a mime type in first parameter, API will accept only this one.

**Also note:*** There are available annotations `@GET`, `@POST`, `@PUT`, `@HEAD`, `@DELETE`. This allows Drahak\Restful library to generate API routes for you so you don't need to do it manually. But it's not necessary! You can define your routes using `IResourceRoute` or its default implementation such as:

```php
<?php
use Drahak\Restful\Application\Routes\ResourceRoute;

$anyRouteList[] = new ResourceRoute('sample[.<type xml|json>]', 'Resources:Sample:content', ResourceRoute::GET);
```

There is only one more parameter unlike the Nette default Route, the request method. This allows you to generate same URL for e.g. GET and POST method. You can pass this parameter to route as a flag so you can combine more request methods such as `ResourceRoute::GET | ResourceRoute::POST` to listen on GET and POST request method in the same route.

You can also define action names dictionary for each request method:

```php
<?php
new ResourceRoute('myResourceName', array(
    'presenter' => 'MyResourcePresenter',
    'action' => array(
        ResourceRoute::GET => 'content',
        ResourceRoute::DELETE => 'delete'
    )
), ResourceRoute::GET | ResourceRoute::DELETE);
```

Simple CRUD resources
---------------------
Well it's nice but in many cases I define only CRUD operations so how can I do it more intuitively? Use `CrudRoute`! This child of `ResourceRoute` pre-defines base CRUD operations for you. Namely, it is `Presenter:create` for POST method, `Presenter:read` for GET, `Presenter:update` for PUT and `Presenter:delete` for DELETE. Then your router will look like this:

```php
<?php
new CrudRoute('<module>/crud', 'MyResourcePresenter');
```
Note the second parameter, metadata. You can define only Presenter not action name. This is because the action name will be replaced by value from actionDictionary (`[CrudRoute::POST => 'create', CrudRoute::GET => 'read', CrudRoute::PUT => 'update', CrudRoute::DELETE => 'delete']`) which is property of `ResourceRoute` so even of `CrudRoute` since it is its child. Also note that we don't have to set flags. Default flags are set to `CrudRoute::CRUD` so the route will match all request methods.

Then you can simple define your CRUD resource presenter:

```php
<?php
namespace ResourcesModule;

/**
 * CRUD resource presenter
 * @package ResourcesModule
 * @author Drahomír Hanák
 */
class CrudPresenter extends BasePresenter
{

    public function actionCreate()
    {
        $this->resource->action = 'Create';
    }

    public function actionRead()
    {
        $this->resource->action = 'Read';
    }

    public function actionUpdate()
    {
        $this->resource->action = 'Update';
    }

    public function actionDelete()
    {
        $this->resource->action = 'Delete';
    }

}
```

Note: every request method can be overridden if you specify `X-HTTP-Method-Override` header in request or by adding query parameter `__method` to URL.

### Let there be relations
Relations are pretty common in RESTful services but how to deal with it in URL? Our goal is something like this `GET /articles/94/comments[/5]` while ID in brackets might be optional. The route will be as follows:

```php
$router[] = new ResourceRoute('api/v1/articles/<id>/comments[/<commentId>]', array(
    'presenter' => 'Articles',
    'action' => array(
        IResourceRouter::GET => 'readComment',
        IResourceRouter::DELETE => 'deleteComment'
    )
), IResourceRouter::GET | IResourceRouter::DELETE);
```

#### Request parameters in action name
It's a quite long. Therefore, there is an option how to generalize it. Now it will look like this:

```php
$router[] = new ResourceRoute('api/v1/<presenter>/<id>/<relation>[/<relationId>]', array(
    'presenter' => 'Articles',
    'action' => array(
        IResourceRouter::GET => 'read<Relation>',
        IResourceRouter::DELETE => 'delete<Relation>'
    )
), IResourceRouter::GET | IResourceRouter::DELETE);
```

Much better but still quite long. Let's use `CrudRoute` again:

```php
$router[] = new CrudRoute('api/v1/<presenter>/<id>/[<relation>[/<relationId>]]', 'Articles');
```

This is the shortest way. It works because action dictionary in `CrudRoute` is basically as follows.

```php
array(
    IResourceRouter::POST => 'create<Relation>',
    IResourceRouter::GET => 'read<Relation>',
    IResourceRouter::PUT => 'update<Relation>',
    IResourceRouter::DELETE => 'delete<Relation>'
)
```

Also have a look at few examples for this single route:
```
    GET api/v1/articles/94                  => Articles:read
    DELETE api/v1/articles/94               => Articles:delete
    GET api/v1/articles/94/comments         => Articles:readComments
    GET api/v1/articles/94/comments/5       => Articles:readComments
    DELETE api/v1/articles/94/comments/5    => Articles:deleteComments
    POST api/v1/articles/94/comments        => Articles:createComments
    ...
```

Of course you can add more then one parameter to action name and make even longer relations.

**Note:** if *relation* or any other parameter in action name does not exist, it will be ignored and name without the parameter will be used.

**Also note:** parameters in action name are **NOT** case-sensitive

Accessing input data
--------------------
If you want to build REST API, you may also want to access query input data for all request methods (GET, POST, PUT, DELETE and HEAD). So the library defines input parser, which reads data and parse it to an array. Data are fetched from query string or from request body and parsed by `IMapper`. First the library looks for request body. If it's not empty it checks `Content-Type` header and determines correct mapper (e.g. for `application/json` -> `JsonMapper` etc.) Then, if request body is empty, try to get POST data and at the end even URL query data.

```php
<?php
namespace ResourcesModule;

/**
 * Sample resource
 * @package ResourcesModule
 * @author Drahomír Hanák
 */
class SamplePresenter extends BasePresenter
{

	/**
	 * @PUT <module>/sample
	 */
	public function actionUpdate()
	{
		$this->resource->message = isset($this->input->message) ? $this->input->message : 'no message';
	}

}
```
Good thing about it is that you don't care of request method. Nette Drahak REST API library will choose correct Input parser for you but it's still up to you, how to handle it. There is available `InputIterator` so you can iterate through input in presenter or use it in your own input parser as iterator.

Input data validation
---------------------
First rule of access to input data: **never trust client**! Really this is very important since it is key feature for security. So how to do it right? You may already know Nette Forms and its validation. Lets do the same in Restful! You can define validation rules for each input data field. To get field (exactly `Drahak\Restful\Validation\IField`), just call `field` method with field name in argument on `Input` (in presenter: `$this->input`). And then define rules (almost) like in Nette:

```php
/**
 * SamplePresenter resource
 * @package Restful\Api
 * @author Drahomír Hanák
 */
class SamplePresenter extends BasePresenter
{

	public function validateCreate()
	{
    	$this->input->field('password')
    		->addRule(IValidator::MIN_LENGTH, NULL, 3)
    		->addRule(IValidator::PATTERN, 'Please add at least one number to password', '/.*[0-9].*/');
	}

    public function actionCreate()
    {
    	// some save data insertion
	}

}
```

That's it! It is not exact the way like Nette but it's pretty similar. At least the base public interface.

**Note**: the validation method `validateCreate`. This new lifecycle method `validate<Action>()` will be processed for each action before the action method `action<Action>()`. It's not required but it's good to use for defining some validation rules or validate data. In case if validation failed throws exception BadRequestException with code HTT/1.1 422 (UnproccessableEntity) that can be handled by error presenter.

Error presenter
---------------
The simplest but yet powerful way to provide readable error response for clients is to use `$presenter->sendErrorResponse(Exception $e)` method. The simplest error presenter could look like as follows:

```php
<?php
namespace Restful\Api;

use Drahak\Restful\Application\UI\ResourcePresenter;

/**
 * Base API ErrorPresenter
 * @package Restful\Api
 * @author Drahomír Hanák
 */
class ErrorPresenter extends ResourcePresenter
{

	/**
	 * Provide error to client
	 * @param \Exception $exception
	 */
	public function actionDefault($exception)
	{
		$this->sendErrorResource($exception);
	}

}
```
Clients can determine preferred format just like in normal API resource. Actually it only adds data from exception to resource and send it to output.

Security & authentication
-------------------------
Restful provides a few ways how to secure your resources:

### BasicAuthentication
This is completely basic but yet powerful way how to secure you resources. It's based on standard Nette user's authentication (if user is not logged in then throws security exception which is provided to client) therefore it's good for trusted clients (such as own client-side application etc.) Since this is common Restful contains `SecuredResourcePresenter` as a children of `ResourcePresenter` which already handles `BasicAuthentication` for you. See example:

```php
use Drahak\Restful\Application\UI\SecuredResourcePresenter

/**
 * My secured resource presenter
 * @author Drahomír Hanák
 */
class ArticlesPresenter extends SecuredResourcePresenter
{

    // all my resources are protected and reachable only for logged user's
    // you can also add some Authorizator to check user rights

}
```

But remember to keep REST API stateless.

### SecuredAuthentication
When there are third-party clients are connected you have to find another way how to authenticate these clients. `SecuredAuthentication` is more or less the answer. It's based on sending hashed data with private key. Since the data is already encrypted, it not depends on SSL. Authentication process is as follows:

### Understanding authentication process
- Client: append request timestamp to request body.
- Client: hash all data with `hash_hmac` (sha256 algorithm) and with private key. Then append generated hash to request as `X-HTTP-AUTH-TOKEN` header (by default).
- Client: sends request to server.
- Server: accepts client's request and calculate hash in the same way as client (using abstract template class `AuthenticationProcess`)
- Server: compares client's hash with hash that it generated in previous step.
- Server: also checks request timestamp and make difference. If it's bigger then 300 (5 minutes) throws exception. (this avoid something called [Replay Attack](http://en.wikipedia.org/wiki/Replay_attack))
- Server: catches any `SecurityException` that throws `AuthenticationProcess` and provides error response.

Default `AuthenticationProcess` is `NullAuthentication` so all requests are unsecured. You can use `SecuredAuthentication` to secure your resources. To do so, just set this authentication process to `AuthenticationContext` in `restful.authentication` or `$presenter->authentication`.

```php
<?php
namespace ResourcesModule;

use Drahak\Restful\Security\Process\SecuredAuthentication;

/**
 * CRUD resource presenter
 * @package ResourcesModule
 * @author Drahomír Hanák
 */
class CrudPresenter extends BasePresenter
{

	/** @var SecuredAuthentication */
	private $securedAuthentication;

	/**
	 * Inject secured authentication process
	 * @param SecuredAuthentication $auth
	 */
	public function injectSecuredAuthentication(SecuredAuthentication $auth)
	{
		$this->securedAuthentication = $auth;
	}

	protected function startup()
	{
		parent::startup();
		$this->authentication->setAuthProcess($this->securedAuthentication);
	}

	// your secured resource action
}
```

##### Never send private key!

Secure your resources with OAuth2
---------------------------------
If you want to secure your API resource with OAuth2, you will need some OAuth2 provider. I've implemented [OAuth2 provider](https://github.com/drahak/OAuth2) bundle for Nette framework so you can use it with Restful. To do so just add dependency `"drahak/oauth2": "dev-master"` to your composer and then use `OAuth2Authentication` which is `AuthenticationProcess`. If you wish to use any other OAuth2 provider, you can write your own `AuthenticationProcess`.

```php
<?php
namespace Restful\Api;

use Drahak\Restful\IResource;
use Drahak\Restful\Security\Process\AuthenticationProcess;
use Drahak\Restful\Security\Process\OAuth2Authentication;

/**
 * CRUD resource presenter
 * @package Restful\Api
 * @author Drahomír Hanák
 */
class CrudPresenter extends BasePresenter
{

	/** @var AuthenticationProcess */
	private $authenticationProcess;

	/**
	 * Inject authentication process
	 * @param OAuth2Authentication $auth
	 */
	public function injectSecuredAuthentication(OAuth2Authentication $auth)
	{
		$this->authenticationProcess = $auth;
	}

	/**
	 * Check presenter requirements
	 * @param $element
	 */
	public function checkRequirements($element)
	{
		parent::checkRequirements($element);
		$this->authentication->setAuthProcess($this->authenticationProcess);
	}

	// ...

}
```

Note: this is only Resource server so it handles access token authorization. To generate access token you'll need to create OAuth2 presenter (Resource owner and authorization server - see [Drahak\OAuth2 documentation](https://github.com/drahak/OAuth2)).

JSONP support
-------------
If you want to access your API resources by JavaScript on remote host, you can't make normal AJAX request on API. So JSONP is alternative how to do it. In JSONP request you load your API resource as a JavaScript using standard `script` tag in HTML. API wraps JSON string to a callback function parameter. It's actually pretty simple but it needs special care. For example you can't access response headers or status code. You can wrap these headers and status code to all your resources but this is not good for normal API clients, which can access header information. The library allows you to add special query parameter `jsonp` (name depends on your configuration, this is default value). If you access resource with `?jsonp=callback` API automatically determines JSONP mode and wraps all resources to following JavaScript:

```javascript
callback({
	"response": {
		"yourResourceData": "here"
	},
	"status": 200,
	"headers": {
		"X-Powered-By": "Nette framework",
		...
	}
})
```
**Note** : the function name. This is name from `jsonp` query parameter. This string is "webalized" by `Nette\Utils\Strings::webalize(jsonp, NULL, FALSE)`. If you set `jsonpKey` to `FALSE` or `NULL` in configuration, you totally disable JSONP mode for all your API resources. Then you can trigger it manually. Just set `IResource` `$contentType` property to `IResource::JSONP`.

**Also note** : if this option is enabled and client adds `jsonp` parameter to query string, no matter what you set to `$presenter->resource->contentType` it will produce `JsonpResponse`.

Utilities that make life better
-------------------------------
Filtering API requests is rut. That's why it's boring to do it all again. Restful provides `RequestFilter` which parses the most common things for you. In `ResourcePresenter` you can find `RequestFilter` in `$requestFilter` property.

### Paginator
By adding `offset` & `limit` parameters to query string you can create standard Nette `Paginator`. Your API resource then response with `Link` header (where "last page" part of `Link` and `X-Total-Count` header are only provided if you set total items count to a paginator)

```
Link: <URL_to_next_page>; rel="next",
      <URL_to_last_page>; rel="last"
X-Total-Count: 1000
```

### Fields list
In case you want to load just a part of a resource (e.g. it's expensive to load whole resource data), you should add `fields` parameter to query params with list of desired fields (e.q. `fields=user_id,name,email`). In `RequestFilter`, you can get this list (`array('user_id', 'name', 'email')`) by calling `getFieldsList()` method.

### Sort list
If you want to sort list provided by resource you will probably need properties according to which you sort. Also, don't forget there is descending and ascending sort. To make it as easy as possible you can get it from `sort` query parameter (such as `query=name,-created_at`) as `array('name' => 'ASC', 'created_at' => 'DESC')` by calling `RequestFilter` method `getSortList()`

___

So that's it. Enjoy and hope you like it!
