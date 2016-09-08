# Wordpress REST Api

Laravel 5.1 wrapper/extension of the popular PHP library for the Wordpress OAuth REST API.

## Installation

In development...

## Configuration

Create your app at [https://apps.Wordpress.com](https://apps.Wordpress.com).

## Usage

In development...

#####Generate Authorize URL:

This generates a URL that points users to Wordpress's authorization page where they can authorize your app. It lists permissions being granted and allow/deny buttons.

``` php
$url = $this->Wordpress->authorizeUrl();
return $url;
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CONDUCT](CONDUCT.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
