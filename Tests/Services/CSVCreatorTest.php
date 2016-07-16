<?php
namespace VKR\CSVBundle\Tests\Services;

use VKR\CSVBundle\Exception\MalformedCSVFieldsException;
use VKR\CSVBundle\Exception\MalformedCSVObjectException;
use VKR\CSVBundle\Services\CSVCreator;
use VKR\CSVBundle\TestHelpers\TestEntity;

class CSVCreatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CSVCreator
     */
    protected $csvCreator;

    /**
     * @var TestEntity[]
     */
    protected $objectData;

    public function setUp()
    {
        $this->csvCreator = new CSVCreator();
    }

    public function testCSVWithObject()
    {
        $data = $this->formObjectData();
        $fields = ['firstProperty', 'secondProperty'];
        $csv = $this->csvCreator->parseDataToCSV($data, $fields);
        $expected = "firstProperty,secondProperty\nfirst,second\nthird,fourth\n";
        $this->assertEquals($expected, $csv);
    }

    public function testCSVWithArray()
    {
        $data = $this->formArrayData();
        $fields = ['firstProperty', 'secondProperty'];
        $csv = $this->csvCreator->parseDataToCSV($data, $fields);
        $expected = "firstProperty,secondProperty\nfirst,second\nthird,fourth\n";
        $this->assertEquals($expected, $csv);
    }

    public function testCSVWithAlternateSeparator()
    {
        $data = $this->formArrayData();
        $fields = ['firstProperty', 'secondProperty'];
        $csv = $this->csvCreator->parseDataToCSV($data, $fields, [], ';');
        $expected = "firstProperty;secondProperty\nfirst;second\nthird;fourth\n";
        $this->assertEquals($expected, $csv);
    }

    public function testCSVWithSeparatorReplacement()
    {
        $data = [
            [
                'firstProperty' => 'fir,st',
                'secondProperty' => 'second',
            ]
        ];
        $fields = ['firstProperty', 'secondProperty'];
        $csv = $this->csvCreator->parseDataToCSV($data, $fields, [], ',', ';');
        $expected = "firstProperty,secondProperty\nfir;st,second\n";
        $this->assertEquals($expected, $csv);
    }

    public function testCSVWithFiller()
    {
        $data = [
            [
                'firstProperty' => 'first',
            ]
        ];
        $fields = ['firstProperty', 'secondProperty'];
        $csv = $this->csvCreator->parseDataToCSV($data, $fields, [], null, null, null, '1');
        $expected = "firstProperty,secondProperty\nfirst,1\n";
        $this->assertEquals($expected, $csv);
    }

    public function testCSVWithDateTime()
    {
        $data = $this->formDateTimeData();
        $fields = ['firstProperty', 'secondProperty'];
        $dateFormat = 'd/m/Y';
        $csv = $this->csvCreator->parseDataToCSV($data, $fields, [], null, null, $dateFormat);
        $expected = "firstProperty,secondProperty\n01/01/2016,01/02/2016\n";
        $this->assertEquals($expected, $csv);
    }

    public function testCSVWithCustomIndexes()
    {
        $data = $this->formArrayData();
        $fields = ['firstProperty', 'secondProperty'];
        $indexes = ['firstIndex', 'secondIndex'];
        $csv = $this->csvCreator->parseDataToCSV($data, $fields, $indexes);
        $expected = "firstIndex,secondIndex\nfirst,second\nthird,fourth\n";
        $this->assertEquals($expected, $csv);
    }

    public function testCSVWithNonTraversableData()
    {
        $data = 'some string';
        $fields = ['firstProperty', 'secondProperty'];
        $exceptionReflection = new \ReflectionClass(MalformedCSVObjectException::class);
        $this->setExpectedException($exceptionReflection->getName());
        $csv = $this->csvCreator->parseDataToCSV($data, $fields);
    }

    public function testCSVWithNonStringFields()
    {
        $data = $this->formArrayData();
        $fields = [new \DateTime()]; // can't be implicitly converted to string
        $exceptionReflection = new \ReflectionClass(MalformedCSVFieldsException::class);
        $this->setExpectedException($exceptionReflection->getName());
        $csv = $this->csvCreator->parseDataToCSV($data, $fields);
    }

    public function testCSVWithCommasInFields()
    {
        $data = $this->formArrayData();
        $fields = ['first,Property', 'secondProperty'];
        $exceptionReflection = new \ReflectionClass(MalformedCSVFieldsException::class);
        $this->setExpectedException($exceptionReflection->getName());
        $csv = $this->csvCreator->parseDataToCSV($data, $fields);
    }

    public function testCSVWithMalformedCustomIndexes()
    {
        $data = $this->formArrayData();
        $fields = ['firstProperty', 'secondProperty'];
        $indexes = ['firstIndex', 'secondIndex', 'thirdIndex'];
        $exceptionReflection = new \ReflectionClass(MalformedCSVFieldsException::class);
        $this->setExpectedException($exceptionReflection->getName());
        $csv = $this->csvCreator->parseDataToCSV($data, $fields, $indexes);
    }

    public function testSetHeaders()
    {
        $headers = $this->csvCreator->setHeaders('my_file');
        $expectedHeaders = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="my_file.csv"',
        ];
        $this->assertEquals($expectedHeaders, $headers);
    }

    protected function formObjectData()
    {
        $firstEntity = new TestEntity();
        $firstEntity->setFirstProperty('first');
        $firstEntity->setSecondProperty('second');
        $secondEntity = new TestEntity();
        $secondEntity->setFirstProperty('third');
        $secondEntity->setSecondProperty('fourth');
        return [$firstEntity, $secondEntity];
    }

    protected function formArrayData()
    {
        $firstEntity = [
            'firstProperty' => 'first',
            'secondProperty' => 'second',
        ];
        $secondEntity = [
            'firstProperty' => 'third',
            'secondProperty' => 'fourth',
        ];
        return [$firstEntity, $secondEntity];
    }

    protected function formDateTimeData()
    {
        $data = [
            [
                'firstProperty' => new \DateTime('2016-01-01'),
                'secondProperty' => new \DateTime('2016-02-01'),
            ],
        ];
        return $data;
    }
}
