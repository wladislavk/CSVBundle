About
=====

This is a simple bundle for CSV creation in Symfony. It doesn't have any configuration
and no dependencies except for Symfony, however it works best if you use Doctrine and
want to parse a query result into CSV format.

Installation
============

Nothing to install except enabling the bundle in ```AppKernel.php```.

Usage
=====

Basic usage
-----------

First, you need to have some kind of data source. This bundle is very flexible about how
your data source looks - basically, it has to be an array or array-like object (anything
that works with ```foreach```), and its elements can be either arrays or objects. In other
words, it will work with any non-null value that is returned by Doctrine's ```getResult()```
or ```getArrayResult()```.

You also need to define fields that will make it into your CSV. If your data source consists
of arrays, those fields are just keys of those arrays. If you work with objects, field names
must comply with the following rule with respect to the object's public getters:

```
$fieldName = lcfirst(str_replace('get', '', $getterName));
```

Once you have a data source and field names, you can form the CSV data in your controller:

```
$dataSource = [
    [
        'name' => 'name1',
        'value' => 'value1',
        'foo' => 'bar',
    ]
];
$fields = ['name', 'value'];
$CSVCreator = $this->get('vkr_csv.csv_creator');
$CSVData = $CSVCreator->parseDataToCSV($dataSource, $fields);
```

The variable ```$CSVData``` will hold the following string:

```
name,value\n
name1,value1\n
```

If there are any commas in the data source values, those will be swapped for whitespaces.

Field labels
-------------

If you want to create 'nice' titles for the first row of your CSV, you can use the third
argument of ```parseDataToCSV()```, array of field labels. If it is not empty, it should
contain the same number of elements as ```$fields```. For example:

```
$dataSource = [
    [
        'element_name' => 'name1',
        'element_value' => 'value1',
    ]
];
$fields = ['element_name', 'element_value'];
$fieldLabels = ['Element name', 'Element value'];
$CSVData = $CSVCreator->parseDataToCSV($dataSource, $fields, $fieldLabels);
```

The resulting data will look as follows:

```
Element name, Element value\n
name1,value1\n
```

Note that neither ```$fields``` nor ```$fieldLabels``` can normally contain separator
symbols, otherwise ```MalformedCSVFieldsException``` will be thrown. However, if you
define a field with a separator symbol and a corresponding label without the separator,
the script will work as usual:

```
$separator = '-';
$fields = ['my-value'];
$fieldLabels = ['My value'];
```

Custom separators
-----------------

If you want to use something else than comma as a separator, you can specify the fourth
argument of ```parseDataToCSV()```. You can also change the separator replacement string
by specifying the fifth argument. Note that separator replacement cannot occur in
both field names and field labels.

```
$dataSource = [
    [
        'name' => 'name 1',
        'value' => 'value 1',
    ]
];
$fields = ['name', 'value'];
$separator = ' ';
$replacement = '_';
$CSVData = $CSVCreator->parseDataToCSV($dataSource, $fields, [], $separator, $replacement);
```

The resulting data will look as follows:

```
name value\n
name_1 value_1\n
```

Handling dates inside data
--------------------------

If an element of your data source cannot be implicitly converted to string, the bundle
will throw ```MalformedCSVObjectException```. The only exception to this are ```DateTime```
objects that are frequently returned by Doctrine. If you expect to receive a ```DateTime```
object, you should specify the sixth argument of ```parseDataToCSV()``` which should be
a format string that is accepted by ```DateTime::format()``` method.

```
$dataSource = [
    [
        'name' => 'name1',
        'date' => new DateTime('2016-02-01'),
    ]
];
$fields = ['name', 'date'];
$format = 'd/m/Y';
$CSVData = $CSVCreator->parseDataToCSV($dataSource, $fields, [], null, null, $format);
```

The resulting data will look as follows:

```
name,date\n
name1,01/02/2016\n
```

If the format string contains separator symbols, those will be replaced as well.

If you did not specify the sixth argument or it is malformed, the bundle will not give
you any errors, rather you will get a standard result of calling ```DateTime::format()```
without arguments, that will depend on your PHP settings.

Fillers
-------

The seventh, and final argument of ```parseDataToCSV()``` determines what to do if
the field value on one or more of elements of your data source is null or undefined.
If this argument is not specified, an empty string is returned. For example:

```
$dataSource = [
    [
        'name' => 'name1',
    ]
];
$fields = ['name', 'price'];
$CSVData = $CSVCreator->parseDataToCSV($dataSource, $fields);
```

The resulting data without ```$filler``` look as follows:

```
name,price\n
name1,\n
```

If you specify that

```
$filler = '0.00';
```

you will get this:

```
name,price\n
name1,0.00\n
```

Separator symbols inside fillers will be replaced.

Forming CSV file from data
--------------------------

The variable returned by ```parseDataToCSV()``` is not yet a CSV file, it is just a
string. To make Symfony return a file, you need to define headers and return a response.
This bundle includes a simple helper function for header definition, ```setHeaders()```.
It accepts an optional ```$filename``` argument which is a file name without extension.
If this argument is not specified, the file will be called ```{current_timestamp}.csv```.

Here is how you return a file from the controller:

```
$headers = $CSVCreator->setHeaders('foo');
$response = new Symfony\Component\HttpFoundation\Response();
foreach ($headers as $headerName => $headerValue) {
    $response->headers->set($headerName, $headerValue);
}
$response->setContent($CSVData);
return $response;
```

The resulting file will be named ```foo.csv```.

API
===

*string CSVCreator::parseDataToCSV(array|Traversable $dataSource, string[] $fields, string[] $fieldLabels = [], string $separator = ',', string $replacement = ' ', string $dateFormat = '', string $filler = '')*

*string[] CSVCreator::setHeaders(string $filename = '')*
