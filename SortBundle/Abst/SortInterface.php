<?php

namespace SortBundle\Abst;

/**
 * Interface dla algorytmów sortujących
 * Interface SortInterface
 * @package SortBundle\Abst
 */
interface SortInterface
{

    /**
     * Sortuje otrzymane dane i zwraca tablice posortowana zależnie od zadanego kierunku
     * @param string $sFileDir
     * @param \Closure|null $Callback
     * @param string $sDirection
     * @return array
     */
    public function sort(string $sFileDir, \Closure $Callback = null, string $sDirection = 'ASC');

}