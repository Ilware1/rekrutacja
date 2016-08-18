<?php
namespace SortBundle\Abst;

/**
 * Klasa abstrakcyjna do algorygmów sortowania
 * Class Sort
 * @package SortBundle\Abst
 */
abstract class Sort implements SortInterface
{

    /**
     * Pobiera strumień danych z pliku po jednej lini
     * @param string $sFileDir
     * @param \Closure|null $Callback
     * @return \Generator
     */
    protected function getDataFromFile(string $sFileDir, \Closure $Callback = null):\Generator
    {
        if (!file_exists($sFileDir)) {
            throw new \InvalidArgumentException('File ' . $sFileDir . ' not exist.');
        }
        $FileResource = fopen($sFileDir, 'r');
        while ($line = fgets($FileResource)) {
            if ($Callback) {
                $Callback();
            }
            yield $line;
        }
    }

    /**
     * Czyści dane z float, usuwa zbędne znaki po wczytaniu z pliku
     * @param $sValue
     * @return float
     */
    protected function clearValue($sValue)
    {
        return preg_replace('/[^0-9\.]/', '', $sValue);
    }

    /**
     * Spłaszcza tablicę wielowymiarową
     * @param array $aArray
     * @return array
     */
    protected function flatten(array $aArray):array
    {
        $aReturn = array();
        array_walk_recursive($aArray, function ($mValue) use (&$aReturn) {
            $aReturn[] = $mValue;
        });
        return $aReturn;
    }

    /**
     * Zamienia wiersz z pliku na tablicę
     * @param string $sData
     * @param string $sDelimiter
     * @return array
     */
    protected function explodeStringData(string $sData, string $sDelimiter = "|"):array
    {
        return explode($sDelimiter, $sData);
    }
}