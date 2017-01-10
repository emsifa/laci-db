<?php

namespace Emsifa\Laci;

use Closure;

class Query
{

    const TYPE_GET = 'get';
    const TYPE_INSERT = 'insert';
    const TYPE_UPDATE = 'update';
    const TYPE_DELETE = 'delete';
    const TYPE_SAVE = 'save';

    protected $collection;

    protected $hasExecuted = false;

    protected $data = null;

    public function __construct(Collection $collection)
    {
        $this->collection = $collection;
    }

    public function getCollection()
    {
        return $this->collection;
    }

    public function setCollection(Collection $collection)
    {
        $this->collection = $collection;
    }

    public function hasExecuted()
    {
        return true === $this->hasExecuted;
    }

    public function all()
    {
        $data = $this->getCollection()->loadData();
        $this->hasExecuted = true;
        return $data;
    }

    public function select(array $columns)
    {
        $resolvedColumns = [];
        foreach($columns as $column) {
            $exp = explode(':', $column);
            $col = $exp[0];
            if (count($exp) > 1) {
                $keyAlias = $exp[1];
            } else {
                $keyAlias = $exp[0];
            }
            $resolvedColumns[$col] = $keyAlias;
        }

        $keyAliases = array_values($resolvedColumns);
        
        return $this->map(function($row) use ($resolvedColumns, $keyAliases) {
            foreach($resolvedColumns as $col => $keyAlias) {
                if (!isset($row[$keyAlias])) {
                    $row[$keyAlias] = $row[$col];
                }
            }

            foreach($row->toArray() as $col => $value) {
                if (!in_array($col, $keyAliases)) {
                    unset($row[$col]);
                }
            }

            return $row;
        });
    }

    public function where($key, $operatorOrValue)
    {
        $args = func_get_args();
        if (count($args) > 2) {
            $operator = $operatorOrValue;
            $value = $args[2];
        } else {
            $operator = '=';
            $value = $operatorOrValue;
        }

        switch($operator) {
            case '=': 
                $filter = function($row) use ($key, $value) {
                    return $row[$key] == $value;
                };
                break;
            case '>':
                $filter = function($row) use ($key, $value) {
                    return $row[$key] > $value;
                };
                break;
            case '>=':
                $filter = function($row) use ($key, $value) {
                    return $row[$key] >= $value;
                };
                break;
            case '<':
                $filter = function($row) use ($key, $value) {
                    return $row[$key] < $value;
                };
                break;
            case '<=':
                $filter = function($row) use ($key, $value) {
                    return $row[$key] <= $value;
                };
                break;
            case 'in':
                $filter = function($row) use ($key, $value) {
                    return in_array($row[$key], (array) $value);
                };
                break;
            case 'not in':
                $filter = function($row) use ($key, $value) {
                    return !in_array($row[$key], (array) $value);
                };
                break;
            case 'match':
                $filter = function($row) use ($key, $value) {
                    return (bool) preg_match($value, $row[$key]);
                };
                break;
            case 'between':
                if (!is_array($value) OR count($value) < 2) {
                    throw new \InvalidArgumentException("Query between need exactly 2 items in array");
                }
                $filter = function($row) use ($key, $value) {
                    $v = $row[$key];
                    return $v >= $value[0] AND $v <= $value[1];
                };
                break;
        }

        if (!$filter) {
            throw new \InvalidArgumentException("Operator {$operator} is not available");
        }

        return $this->filter($filter);
    }

    public function skip($offset)
    {
        $data = $this->data();
        $limit = count($data);
        $this->data = array_slice($data, $offset, $limit);
        return $this;
    }

    public function take($limit, $offset = 0)
    {
        $this->data = array_slice($this->data(), $offset, $limit);
        return $this;
    }

    public function sortBy($key, $asc = 'asc')
    {
        $asc = strtolower($asc);
        if (!in_array($asc, ['asc', 'desc'])) {
            throw new \InvalidArgumentException("Ascending must be 'asc' or 'desc'", 1);
        }

        return $this->sort(function($a, $b) use ($key, $asc) {
            $valueA = $a[$key];
            $valueB = $b[$key];
            if ('asc' == $asc) {
                return $valueA < $valueB ? -1 : 1;
            } else {
                return $valueA > $valueB ? -1 : 1;
            }
        });
    }

    public function sort(Closure $comparator)
    {
        $data = $this->data();
        uasort($data, function($a, $b) use ($comparator) {
            $a = new ArrayExtra($a);
            $b = new ArrayExtra($b);
            return $comparator($a, $b);
        });
        $this->data = $data;
        return $this;
    }

    public function map(Closure $mapper)
    {
        $keyId = $this->getCollection()->getKeyId();
        $keyOldId = $this->getCollection()->getKeyOldId();

        $this->data = array_map(function($row) use ($mapper, $keyId, $keyOldId) {
            $row = new ArrayExtra($row);
            $result = $mapper($row);
            
            if (is_array($result)) {
                $new = $result;
            } elseif($result instanceof ArrayExtra) {
                $new = $result->toArray();
            } else {
                $new = null;
            }

            if (is_array($new) AND isset($new[$keyId])) {
                if ($row[$keyId] != $new[$keyId]) {
                    $new[$keyOldId] = $row[$keyId];
                }
            }

            return $new;
        }, $this->data());

        return $this;
    }

    public function filter(Closure $filter)
    {
        $this->data = array_filter($this->data(), function($row) use ($filter) {
            $row = new ArrayExtra($row);
            return $filter($row);
        });
        return $this;
    }

    public function get(array $select = [])
    {
        if (!empty($select)) {
            $this->select($select);
        }
        return $this->getCollection()->execute($this, self::TYPE_GET);
    }

    public function first(array $select = array())
    {
        $data = $this->take(1)->get($select);
        return array_shift($data);
    }

    public function update(array $new)
    {
        return $this->getCollection()->execute($this, self::TYPE_UPDATE, $new);
    }

    public function delete()
    {
        return $this->getCollection()->execute($this, self::TYPE_DELETE);
    }

    public function save()
    {
        return $this->getCollection()->execute($this, self::TYPE_SAVE);
    }

    public function count()
    {
        return count($this->get());
    }

    public function sum($key)
    {
        $sum = 0;
        foreach($this->get() as $data) {
            $data = new ArrayExtra($data);
            $sum += $data[$key];
        }
        return $sum;
    }

    public function avg($key)
    {
        $sum = 0; 
        $count = 0;
        foreach($this->get() as $data) {
            $data = new ArrayExtra($data);
            $sum += $data[$key];
            $count++;
        }
        return $sum / $count;
    }

    public function lists($key, $resultKey = null)
    {
        $result = [];
        foreach($this->get() as $i => $data) {
            $data = new ArrayExtra($data);
            $k = $resultKey ? $data[$resultKey] : $i;
            $result[$k] = $data[$key];
        }
        return $result;
    }

    public function pluck($key, $resultKey = null)
    {
        return $this->lists($key, $resultKey);
    }

    public function min($key)
    {
        return min($this->lists($key));
    }

    public function max($key)
    {
        return max($this->lists($key));
    }

    public function withOne($relation, $as, $otherKey, $operator = '=', $thisKey = '_id')
    {
        if (false == $relation instanceof Query AND false == $relation instanceof Collection) {
            throw new \InvalidArgumentException("Relation must be instanceof Query or Collection", 1);
        }
        return $this->map(function($row) use ($relation, $as, $otherKey, $operator, $thisKey) {
            $otherData = $relation->where($otherKey, $operator, $row[$thisKey])->first();
            $row[$as] = $otherData;
            return $row;
        });
    }

    public function withMany($relation, $as, $otherKey, $operator = '=', $thisKey = '_id')
    {
        if (false !== $relation instanceof Query AND false == $relation instanceof Collection) {
            throw new \InvalidArgumentException("Relation must be instanceof Query or Collection", 1);
        }
        return $this->map(function($row) use ($relation, $as, $otherKey, $operator, $thisKey) {
            $otherData = $relation->where($otherKey, $operator, $row[$thisKey])->get();
            $row[$as] = $otherData;
            return $row;
        }); 
    }

    public function data()
    {
        if (is_null($this->data)) {
            $data = $this->getCollection()->loadData();
            $this->data = $data;
        }
        return $this->data;
    }

}