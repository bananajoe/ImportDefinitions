<?php

namespace ImportDefinitions\Model\Provider;

use ImportDefinitions\Model\AbstractProvider;
use ImportDefinitions\Model\Definition;
use ImportDefinitions\Model\Mapping\FromColumn;
use Pimcore\Model\Object\Concrete;

/**
 * CSV Import Provider
 *
 * Class Csv
 * @package ImportDefinitions\Provider
 */
class Csv extends AbstractProvider {

    /**
     * @var string
     */
    public $csvExample;

    /**
     * @var string
     */
    public $delimiter;

    /**
     * @var string
     */
    public $enclosure;

    /**
     * @return string
     */
    public function getCsvExample()
    {
        return $this->csvExample;
    }

    /**
     * @param string $csvExample
     */
    public function setCsvExample($csvExample)
    {
        $this->csvExample = $csvExample;
    }

    /**
     * @return string
     */
    public function getDelimiter()
    {
        return $this->delimiter;
    }

    /**
     * @param string $delimiter
     */
    public function setDelimiter($delimiter)
    {
        $this->delimiter = $delimiter;
    }

    /**
     * @return string
     */
    public function getEnclosure()
    {
        return $this->enclosure;
    }

    /**
     * @param string $enclosure
     */
    public function setEnclosure($enclosure)
    {
        $this->enclosure = $enclosure;
    }

    /**
     * test data
     * 
     * @return boolean
     * @throws \Exception
     */
    public function testData()
    {
        return true;
    }
    
    /**
     * Get Columns from data
     *
     * @return FromColumn[]
     */
    public function getColumns()
    {
        $returnHeaders = [];
        $rows = str_getcsv($this->getCsvExample(), "\n"); //parse the rows

        if(count($rows) > 0) {
            $headerRow = $rows[0];

            $headers = str_getcsv($headerRow, $this->getDelimiter(), $this->getEnclosure());

            if(count($headers) > 0) {
                //First line are the headers
                foreach($headers as $header) {
                    $headerObj = new FromColumn();
                    $headerObj->setIdentifier($header);
                    $headerObj->setLabel($header);

                    $returnHeaders[] = $headerObj;
                }
            }

        }

        return $returnHeaders;
    }

    /**
     * @param Definition $definition
     * @param $params
     * @return Concrete[]
     */
    protected function runImport($definition, $params)
    {
        $file = PIMCORE_DOCUMENT_ROOT . "/" . $params['file'];

        $columnMapping = [];
        $objects = [];

        $row = 0;
        if (($handle = fopen($file, "r")) !== false) {
            while (($data = fgetcsv($handle, 1000, $this->getDelimiter(), $this->getEnclosure())) !== false) {
                $num = count($data);

                //Make Column Mapping
                if($row === 0) {
                    for ($c = 0; $c < $num; $c++) {
                        $columnMapping[] = $data[$c];
                    }
                }
                else {
                    $objects[] = $this->importRow($definition, $columnMapping, $data);
                }

                $row++;
            }
            fclose($handle);
        }

        return $objects;
    }

    /**
     * @param Definition $definition
     * @param $map
     * @param $data
     *
     * @return Concrete
     */
    private function importRow($definition, $map, $data) {
        //Convert Data to map
        $mappedData = [];

        foreach($data as $index => $col) {
            $mappedData[$map[$index]] = $col;
        }

        $object = $this->getObjectForPrimaryKey($definition, $mappedData);

        foreach($definition->getMapping() as $map) {
            $value = $mappedData[$map->getFromColumn()];

            $this->setObjectValue($object, $map, $value, $mappedData);
        }

        $object->save();

        return $object;
    }
}