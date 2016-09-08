# Wordpress REST Api

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Total Downloads][ico-downloads]][link-downloads]

Laravel 5.1 wrapper/extension of the popular PHP library for the Wordpress OAuth REST API.

## Installation

To install, run the following in your project directory

``` bash
$ composer require emmanueln-nike/Wordpress-api
```

Then in `config/app.php` add the following to the `providers` array:

```
n1k3\WordpressApi\WordpressApiServiceProvider::class
```

Also in `config/app.php`, add the Facade class to the `aliases` array, should you want to use it:

```
'WordpressApi'    => n1k3\WordpressApi\Facades\WordpressApi::class
```

## Configuration

To publish Shorty's configuration file, run the following `vendor:publish` command:

```
php artisan vendor:publish --provider="n1k3\WordpressApi\WordpressApiServiceProvider"
```

This will create a Wordpress-api.php file in your config directory. Here you **must** enter your Wordpress App Consumer Key, Consumer Secret, and Callback URL. Create your app at [https://apps.Wordpress.com](https://apps.Wordpress.com).

## Usage

**Be sure to include the namespace for the class wherever you plan to use this library and set construct**

```
use n1k3\WordpressApi\WordpressApi;

public function __construct(WordpressApi $Wordpress)
{
    $this->Wordpress = $Wordpress;
}
```

#####Generate Authorize URL:

This generates a URL that points users to Wordpress's authorization page where they can authorize your app. It lists permissions being granted and allow/deny buttons.

``` php
$url = $this->Wordpress->authorizeUrl();
return $url;
```

#####Get user's access tokens

At this point we will use the temporary request token to get the long lived access_token that authorized to act as the user.

``` php
// This is the callback route, which would likely be a controller method. But for example purposes, see below...
Route::get('oauth/Wordpress', function(Request $request) {
    
    // If the oauth_token is different from the one you sent them to Wordpress with, abort authorization
    if (isset($request->oauth_token) && Session::get('oauth_token') !== $request->oauth_token) 
    {
        Session::forget('oauth_token');
        Session::forget('oauth_token_secret');
        abort(404);
    }

    $Wordpress = new WordpressApi(Session::get('oauth_token'), Session::get('oauth_token_secret'));
    $access_token = $Wordpress->accessToken($request->oauth_verifier);

    return $access_token;
});
```

**This will return an object like the following. This is the important part where you save the credentials to your database of choice to make future calls.**

```
{
	"oauth_token": "24073951-WiFyIePIerPAhQuoZ8VUMq8I4df14jzMcYR7uE6rJ7",
	"oauth_token_secret": "b1lSZ2cPk4DbTP934SCfn1BTVPljdvMEMqSy8asczIGFh",
	"user_id": "24133471",
	"screen_name": "MikeBarwick",
	"x_auth_expires": "0"
}
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CONDUCT](CONDUCT.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/mbarwick83/Wordpress-api.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/mbarwick83/Wordpress-api.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/mbarwick83/Wordpress-api
[link-downloads]: https://packagist.org/packages/mbarwick83/Wordpress-api
[link-author]: https://github.com/mbarwick83
[link-contributors]: ../../contributors
