# Laravel 5.4 TCPDF
[![Latest Stable Version](https://poser.pugx.org/elibyy/tcpdf-laravel/v/stable)](https://packagist.org/packages/elibyy/tcpdf-laravel) [![Total Downloads](https://poser.pugx.org/elibyy/tcpdf-laravel/downloads)](https://packagist.org/packages/elibyy/tcpdf-laravel) [![Latest Unstable Version](https://poser.pugx.org/elibyy/tcpdf-laravel/v/unstable)](https://packagist.org/packages/elibyy/tcpdf-laravel) [![License](https://poser.pugx.org/elibyy/tcpdf-laravel/license)](https://packagist.org/packages/elibyy/tcpdf-laravel)

A simple [Laravel 5](http://www.laravel.com) service provider with some basic configuration for including the [TCPDF library](http://www.tcpdf.org/)

### Note: The Package code is changed to avoid the confusion, this repository is a replacement to the  [old](https://github.com/elibyy/laravel-tcpdf) one 

#### Note: The versions are now as laravel 5.x

## Installation

The Laravel TCPDF service provider can be installed via [composer](http://getcomposer.org) by requiring the `elibyy/tcpdf-laravel` package in your project's `composer.json`. (The installation may take a while, because the package requires TCPDF. Sadly its .git folder is very heavy)

```json
{
    "require": {
        "elibyy/tcpdf-laravel": "5.4.*"
    }
}
```

Next, add the service provider to `config/app.php`.

```php
'providers' => [
    //...
    Elibyy\TCPDF\ServiceProvider::class,
]

//...

'aliases' => [
	//...
	'PDF' => Elibyy\TCPDF\Facades\TCPDF::class
]

```

That's it! You're good to go.

Here is a little example:

```php
use PDF; // at the top of the file

	PDF::SetTitle('Hello World');
	PDF::AddPage();
	PDF::Write(0, 'Hello World');
	PDF::Output('hello_world.pdf');
```

another example for generating multiple PDF's

```php
use PDF; // at the top of the file
	for ($i = 0; $i < 5; $i++) {
		PDF::SetTitle('Hello World'.$i);
		PDF::AddPage();
		PDF::Write(0, 'Hello World'.$i);
		PDF::Output(public_path('hello_world' . $i . '.pdf'), 'F');
		PDF::reset();
	}
```

For a list of all available function take a look at the [TCPDF Documentation](http://www.tcpdf.org/doc/code/classTCPDF.html)

## Configuration

Laravel-TCPDF comes with some basic configuration.
If you want to override the defaults, you can publish the config, like so:

    php artisan vendor:publish

Now access `config/tcpdf.php` to customize.

## Header/Footer helpers

I've got a pull-request asking for this so I've added the feature

now you can use `PDF::setHeaderCallback(function($pdf){})` or `PDF::setFooterCallback(function($pdf){})`
