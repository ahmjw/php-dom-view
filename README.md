# Renders output to HTML view via DOM
Homepage http://chupoo.introvesia.com

# Special HTML Tags
## c.content
By writing `<c.content></c.content>` or `<c.content />` in your HTML template, it will replace it with view's content.
## c.import
You can call another HTML file by writing `<c.import name="menu"></c.import>` or `<c.import name="menu" />`. Write the name of HTML template in attribut `name`. The value `menu` will call HTML template file with name "menu.html". It will replace it with the content of called HTML file.
## c.config
By writing `<c.config layout="two-columns"></c.config>` or `<c.config layout="two-columns" />` in view HTML, it will execute layout as it declared. Write the name of HTML template in attribut `layout`. The value `two-columns` will call HTML template file with name "two-columns.html".
## c.widget
It will mark the area as widget area. In it process, system will collect all widgets and store it to widget list. It will render widget HTML file when you send the widget information as feedback. To mark area to show it as widget, write `<c.widget name="sidebar"></c.widget>` or `<c.widget name="sidebar" />`. Write the widget key in attribut `name`. It will put the `name` value to widget list.

# Extracting Singular Variables to HTML
You can extract variable to HTML by defining data with key and value. This library will render the data to HTML element by key. It compares key of data and the specified special attribute in a HTML element.
## Code on controller
```
$data = array(
  'name' => 'Adam Smith',
  'nationality' => 'USA'
);
```
## Code on HTML
```
<p c.name></p>
<p c.nationality></p>
```

# Looping in HTML
To make looping in a HTML tag, describe the data as array with specified key. Use the array key as an attribut in looping HTML element target.
## Code in controller
```
$data = array(
  'people' => array(
    array(
      'name' => 'Adam Smith',
      'nationality' => 'USA'
     ),
    array(
      'name' => 'Hiroko Yamada',
      'nationality' => 'Japan'
     ),
    array(
      'name' => 'Surya Wijaya',
      'nationality' => 'Indonesia'
     )
   )
);
```
## Looping in HTML
```
<table border="1">
<tr>
  <th>Name</th>
  <th>Nationality</th>
</tr>
<tr c.people>
  <td c.name></td>
  <td c.nationality></td>
</tr>
</table>
```
