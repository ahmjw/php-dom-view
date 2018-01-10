# About
A PHP library to extract and write variables to HTML page by using special attributes. It makes the creation of view be neat and simple.

# Author Profile
Ahmad (<rawndummy@gmail.com>)

Homepage: http://chupoo.introvesia.com

# Requirements
- PHP 5.3 or later
- PSR-4 compatible autoloader
- Composer

# Installation
This library is developed to be installed via Composer. Make sure you have installed Composer in your computer. You can download it at
URL https://getcomposer.org/download/.

Run this command below at command console.
```
composer require ahmjw/php-dom-view
```
Or make a file named as composer.json. Write down this code below.
```
{
    "require": {
        "ahmjw/chupoo-framework": "^1.0"
    }
}
```
Open the command console at the same directory with composer.json file. Execute this code below to install.
```
php composer.phar install
```
# Autoloading
```
<?php

include 'vendor/autoload.php';
```

# Special HTML Tags
## c.content
By writing `<c.content></c.content>` or `<c.content />` in your HTML template, it will replace it with view's content.
## c.import
You can call another HTML file by writing `<c.import name="menu"></c.import>` or `<c.import name="menu" />`. Write the name of HTML template in attribut `name`. The value `menu` will call HTML template file with name "menu.html". It will replace it with the content of called HTML file.
## c.config
By writing `<c.config layout="two-columns"></c.config>` or `<c.config layout="two-columns" />` in view HTML, it will execute layout as it declared. Write the name of HTML template in attribut `layout`. The value `two-columns` will call HTML template file with name "two-columns.html".
## c.widget
It will mark the area as widget area. In it process, system will collect all widgets and store it to widget list. It will render widget HTML file when you send the widget information as feedback. To mark area to show it as widget, write `<c.widget name="sidebar"></c.widget>` or `<c.widget name="sidebar" />`. Write the widget key in attribut `name`. It will put the `name` value to widget list.
# Special HTML Element attributes
## c.if
Defines an expression to control visibility of a HTML element. You can define the expression by following this format below.
- Checks a key exists at global data: `var(x)`
- Checks a key doesn't exist at global data: `!var(x)`
- Checks a key has value equal to the right operand: `var(x) = 1`
- Checks a key has value not equal to the right operand: `var(x) != 1`
- Checks a key in an array by explore its depth: `var(x.y.z)`
- Checks a key linking to current data used by current element: `var(.x)`
**Note:** `x` is the key name in data. `1` is a value. You can define it as number or string.

# Example Codes
## Autoloading and Class Definition (index.php)
```
<?php

use Introvesia\PhpDomView\View;
use Introvesia\PhpDomView\Layout;
use Introvesia\PhpDomView\Config;

include 'vendor/autoload.php';
```
## Set configuration
`layout_dir` is the directory location to put your HTML layout files.
`view_dir` is the directory location to put yout HTML view files.
```
Config::setData(array(
	'layout_dir' => __DIR__ . '/layouts',
	'view_dir' => __DIR__ . '/views'
));
```
## Sample Data for View
```
$view_data = array(
  'title' => 'People',
  'people' => array(
    array(
      'name' => 'Adam Smith',
      'nationality' => 'USA'
     ),
    array(
      'name' => 'Kenji Yamada',
      'nationality' => 'Japan'
     ),
    array(
      'name' => 'Surya Wijaya',
      'nationality' => 'Indonesia'
     )
   )
);
```
## Sample Data for Layout
```
$layout_data = array(
  'meta_title' => 'My Blog',
);
```
## Showing Output
```
$view = new View('index', $view_data);
$layout = new Layout('index', $layout_data);
$layout->parse($view);
print $layout->getOutput();
```
## View HTML code (views/index.html)
```
<h1 c.title></h1>
<table border="1" cellpadding="5">
<tr>
  <th>Name</th>
  <th>Nationality</th>
</tr>
<tr c.people>
  <td c.name>This is name column</td>
  <td c.nationality></td>
</tr>
</table>
```
## Layout HTML code (layouts/index.html)
```
<!DOCTYPE html>
<html>
<head>
  <title>My Site</title>
</head>
<body>
  <table cellpadding="20">
    <tr>
      <td valign="top">
      	<c.widget name="sidebar"></c.widget>
      </td>
      <td valign="top">
      	<c.content></c.content>
      </td>
    </tr>
  </table>
</body>
</html>
```
## Widget `menu` HTML code
```
<h3 c.menu_title></h3>
<ul>
  <li c.links><a href="" c.link></a></li>
</ul>
```
## Rendering View to Widget
```
$links = array(
  array('link' => array('Link 1', 'href' => '#first')),
  array('link' => array('Link 2', 'href' => '#second')),
  array('link' => array('Link 3', 'href' => '#third')),
);

$layout->renderWidget('sidebar', array(
  new View('menu', array(
    'menu_title' => 'My Menu',
    'links' => $links
  )),
));
```
## Defining Expression
By adding attribut `c.if` to element `tr` with variable selector `c.people`, this sample code below will show only people who
are not from USA.
```
...
<tr c.people c.if="var(.nationality) != USA">
...
```
## Making Layout Part (layouts/navbar.html)
```
<ul class="nav">
  <li><a href="#home">Home</a></li>
  <li><a href="#contact">Contact</a></li>
  <li><a href="#about">About</a></li>
</ul>
```
## Importing Other Layout
```
...
<c.import name="navbar"></c.import>
...
```
## Separating Layout to Partial Layout (layouts/header.html)
```
<!DOCTYPE html>
<html>
<head>
	<title>My Site</title>
</head>
```
## Calling Partial Layout
```
<c.partial name="header"></c.partial>
...
```
