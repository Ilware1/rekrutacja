<?php

namespace SortBundle\Services;

use Doctrine\ORM\EntityManager;
use SortBundle\Abst\Sort;

class Database extends Sort
{

    /**
     * @var EntityManager
     */
    protected $Em;

    /**
     * Database constructor.
     * @param EntityManager $Em
     */
    public function __construct(EntityManager $Em)
    {
        $this->Em = $Em;
    }

    /**
     * @inheritdoc
     */
    public function sort(string $sFileDir, \Closure $Callback = null, string $sDirection = 'ASC')
    {
        $Generator = $this->getDataFromFile($sFileDir, $Callback);
        $bDataBaseExist = false;
        try {

            foreach ($Generator as $sFileRow) {
                if ($Callback) {
                    $Callback();
                }
                $aRowData = $this->explodeStringData($sFileRow);
                if (!$bDataBaseExist) {
                    $bDataBaseExist = $this->createTable($this->findPrecisionSize($aRowData));
                }
                $this->insertDataToDatabase($aRowData);
            }
        } catch (\Doctrine\DBAL\DBALException $Exc) {
        }
        $aSortedVaue = $this->getSortedData($sDirection);
        $this->drobTable();
        return $aSortedVaue;
    }

    /**
     * Pobiera posortowane dane z bazy według wskazanego kierunku
     * @param string $sDir
     * @return array
     */
    protected function getSortedData(string $sDir = "ASC")
    {
        if (!in_array(strtolower($sDir), ['asc', 'desc'])) {
            throw new \InvalidArgumentException('Sort posiility is ASC or DESC');
        }
        try {
            $statement = $this->Em->getConnection()->prepare('SELECT numbers FROM filesort ORDER BY numbers ' . $sDir);
            $statement->execute();
            return $statement->fetchAll(\PDO::FETCH_COLUMN, 0);
        } catch (\Doctrine\DBAL\DBALException $exc) {
            return [];
        }
    }

    /**
     * Na podstawie pierwszych 60 liczb aproksymuje jak jst maxymalna precyzja floata
     * @param $aRowData
     * @return int
     */
    protected function findPrecisionSize($aRowData)
    {
        $iMax = 0;
        foreach ($aRowData as $key => $fValue) {
            $iTmpMax = $this->getPrecisionSize($fValue);
            if ($iTmpMax > $iMax) {
                $iMax = $iTmpMax;
            }
            if ($key > 60) {
                break;
            }
        }
        return $iMax;
    }

    /**
     * Zwraca długość floata po .
     * @param $fNumber
     * @return int
     */
    protected function getPrecisionSize($fNumber):int
    {
        return strlen(substr($fNumber, strpos($fNumber, '.') + 1));
    }

    /**
     * Wstawia dane do bazy danych
     * @param array $aData
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function insertDataToDatabase(array $aData)
    {
        $Connection = $this->Em->getConnection();
        $sSqlValues = "";
        foreach ($aData as $fValue) {
            $sSqlValues .= "(" . (float)$this->clearValue($fValue) . "),";
        }
        if (empty($sSqlValues)) {
            return false;
        }
        $Stmt = $Connection->prepare("INSERT INTO filesort(numbers) values " . substr($sSqlValues, 0, -1));
        return $Stmt->execute();
    }

    /**
     * Tworzy tabele w bazie zależnie od potrzb z oreslona precyzja
     * @param int $iPrecision
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function createTable(int $iPrecision):bool
    {
        $SQL = "DROP TABLE IF EXISTS filesort ;
                CREATE TABLE filesort (
                    numbers FLOAT(10,:Precision)
                ) ENGINE=MyISAM";
        $Connection = $this->Em->getConnection();
        $Stmt = $Connection->prepare($SQL);
        $Stmt->bindParam(':Precision', $iPrecision, \PDO::PARAM_INT);
        return $Stmt->execute();
    }

    /**
     * Usuwa tabele z bazy danych
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function drobTable()
    {
        $SQL = "DROP TABLE IF EXISTS filesort;";
        $Connection = $this->Em->getConnection();
        $Stmt = $Connection->prepare($SQL);
        return $Stmt->execute();
    }

}