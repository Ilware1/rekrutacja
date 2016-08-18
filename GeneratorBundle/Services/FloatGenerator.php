<?php

namespace GeneratorBundle\Services;

class FloatGenerator
{

    /**
     * Generuje liczbę z zakresu Min Max o podanej precyzji
     * @param $iMin
     * @param $iMax
     * @param int $round 
     * @return mixed
     */
    public function getNextNumber($iMin, $iMax, $iRound = 0)
    {
        $randomfloat = $iMin + mt_rand() / mt_getrandmax() * ($iMax - $iMin);
        if ($iRound > 0)
            $randomfloat = round($randomfloat, $iRound);

        return $randomfloat;
    }

}