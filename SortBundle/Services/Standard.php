<?php

namespace SortBundle\Services;

use SortBundle\Abst\Sort;

class Standard extends Sort
{

    /**
     * @inheritdoc
     */
    public function sort(string $sFileDir, \Closure $Callback = null, string $sDirection = 'ASC')
    {
        $Generator = $this->getDataFromFile($sFileDir, $Callback);
        $aReturn = [];
        foreach ($Generator as $sFileRow) {
            if ($Callback) {
                $Callback();
            }
            $aReturn[] = $this->explodeStringData($sFileRow);
        }
        $aReturn = $this->flatten($aReturn);
        natsort($aReturn);
        if ($Callback) {
            $Callback();
        }
        if (strtolower($sDirection) == "desc") {
            arsort($aReturn);
        }
        return $aReturn;
    }
}