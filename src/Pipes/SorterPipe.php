<?php

namespace Emsifa\Laci\Pipes;

use Closure;

class SorterPipe implements PipeInterface
{

    protected $comparator;

    public function __construct(Closure $comparator)
    {
        $this->comparator = $comparator;
    }

    public function process(array $data)
    {
        uasort($data, $this->comparator);   
        return $data;
    }

}
