## Glook Apist

[![Latest Stable Version](https://img.shields.io/packagist/v/glook/apist.svg?style=flat)](https://packagist.org/packages/glook/apist)
[![Total Downloads](https://img.shields.io/packagist/dm/glook/apist.svg?style=flat)](https://packagist.org/packages/glook/apist)
[![Tests](https://github.com/glook/apist/workflows/Tests/badge.svg)](https://github.com/glook/apist/actions?query=workflow%3ATests)
[![License](https://img.shields.io/packagist/l/glook/apist.svg?style=flat)](LICENSE)

> Fork of [sleeping-owl/apist](https://github.com/sleeping-owl/apist) with updated dependencies.

Glook Apist is a small library which allows you to access any site in api-like style, based on html parsing.

## Install the Package

From [Packagist](https://packagist.org/packages/glook/apist):

```
 composer require glook/apist
```

## Usage

### Basic example

Create a class that extends `Apist` and define your API methods using blueprints — arrays that map keys to CSS selectors
with extraction chains:

```php
use glook\apist\Apist;

class WikiApi extends Apist
{

	public function getBaseUrl(): ?string
	{
		return 'https://en.wikipedia.org';
	}

	public function index()
	{
		return $this->get('/wiki/Main_Page', [
			'welcome_message'  => Apist::filter('#mp-topbanner div:first')->text()->mb_substr(0, -1),
			'portals'          => Apist::filter('a[title^="Portal:"]')->each([
				'link'  => Apist::current()->attr('href')->call(function ($href)
				{
					return $this->getBaseUri() . $href;
				}),
				'label' => Apist::current()->text()
			]),
			'languages'        => Apist::filter('#p-lang li a[title]')->each([
				'label' => Apist::current()->text(),
				'lang'  => Apist::current()->attr('title'),
				'link'  => Apist::current()->attr('href')->call(function ($href)
				{
					return 'https:' . $href;
				})
			]),
			'sister_projects'  => Apist::filter('#mp-sister b a')->each()->text(),
			'featured_article' => Apist::filter('#mp-tfa')->html()
		]);
	}
}
```

Result:

```json
{
  "welcome_message": "Welcome to Wikipedia",
  "portals": [
    {
      "link": "https:\/\/en.wikipedia.org\/wiki\/Portal:Arts",
      "label": "Arts"
    },
    {
      "link": "https:\/\/en.wikipedia.org\/wiki\/Portal:Biography",
      "label": "Biography"
    }
  ],
  "languages": [
    {
      "label": "Simple English",
      "lang": "Simple English",
      "link": "https:\/\/simple.wikipedia.org\/wiki\/"
    }
  ],
  "sister_projects": [
    "Commons",
    "MediaWiki"
  ],
  "featured_article": "<div style=\"float: left;\">...</div>"
}
```

### Blueprint concept

A **blueprint** is an array (or a single selector) that describes how to extract structured data from HTML. Each value
in the blueprint is an `ApistSelector` — a chain of CSS selector + extraction methods.

```php
// Array blueprint — returns associative array
$this->get('/page', [
    'title'   => Apist::filter('h1')->text(),
    'content' => Apist::filter('.body')->html(),
]);

// Single selector blueprint — returns a single value
$this->get('/page', Apist::filter('h1')->text());

// No blueprint — returns raw HTML content
$this->get('/page');
```

### Static helpers

- `Apist::filter('.selector')` — creates an `ApistSelector` bound to a CSS selector
- `Apist::current()` — references the current element inside `each()` iterations

### HTTP methods

All standard HTTP methods are supported:

```php
$this->get($url, $blueprint, $options);
$this->post($url, $blueprint, $options);
$this->put($url, $blueprint, $options);
$this->patch($url, $blueprint, $options);
$this->delete($url, $blueprint, $options);
$this->head($url, $blueprint, $options);
```

The `$options` array is passed directly to Guzzle's request options.

### Filter methods

Extraction methods available in selector chains:

| Method            | Description               |
|-------------------|---------------------------|
| `text()`          | Get text content          |
| `html()`          | Get inner HTML            |
| `attr('name')`    | Get attribute value       |
| `hasAttr('name')` | Check if attribute exists |
| `exists()`        | Check if element exists   |

Navigation:

| Method               | Description                        |
|----------------------|------------------------------------|
| `first()`            | First matched element              |
| `last()`             | Last matched element               |
| `eq($index)`         | Element at index                   |
| `next()`             | Next sibling                       |
| `prev()`             | Previous sibling                   |
| `children()`         | Child elements                     |
| `closest($selector)` | Closest ancestor matching selector |

Iteration:

| Method             | Description                     |
|--------------------|---------------------------------|
| `each($blueprint)` | Iterate over matched elements   |
| `each()`           | Returns collection for chaining |

Transformation:

| Method                           | Description               |
|----------------------------------|---------------------------|
| `call($callback)`                | Apply custom callback     |
| `check($condition, $then)`       | Conditional logic         |
| `then($blueprint)`               | Apply blueprint if truthy |
| `trim()`                         | Trim whitespace           |
| `intval()`                       | Cast to integer           |
| `floatval()`                     | Cast to float             |
| `str_replace($search, $replace)` | String replacement        |
| `mb_substr($start, $length)`     | Multibyte substring       |

Any global PHP function can also be used in the chain (e.g., `strtoupper`, `strip_tags`).

### YAML API

You can define your API using YAML instead of PHP classes:

```php
use glook\apist\Apist;

$api = Apist::fromYaml($yamlContent);
$result = $api->myMethod();
```

YAML format:

```yaml
baseUri: https://example.com

_post:
  title: .post-title | text
  date: .post-date | text | trim

listPosts:
  url: /posts
  blueprint:
    posts: .post | each | :post

getPost:
  url: /posts/$1
  method: get
  blueprint:
    post: :post
```

- `baseUri` — base URL for all requests
- Keys starting with `_` define reusable structures
- `$1`, `$2` — positional arguments passed to the method
- `:structureName` — reference to a defined structure
- Pipe syntax: `selector | method1 | method2`

### Error handling

By default, HTTP errors are suppressed and returned as structured error responses:

```php
$api->setSuppressExceptions(false); // throw exceptions on HTTP errors
```

### Access to Guzzle response

After a request, you can access the underlying Guzzle response:

```php
$result = $api->index();
$response = $api->getLastMethod()->getResponse();
$statusCode = $response->getStatusCode();
```

## Testing

```bash
composer test
```

## Supported PHP Versions

That package has been tested on the following PHP versions:

- PHP 7.4
- PHP 8.0
- PHP 8.1
- PHP 8.2
- PHP 8.3
- PHP 8.4
- PHP 8.5

## Copyright and License

Originally written by [Sleeping Owl](https://github.com/sleeping-owl) and released under the MIT License.

Fork maintained by [Andrey Polyakov](https://github.com/glook).

See the [LICENSE](LICENSE) file for details.
