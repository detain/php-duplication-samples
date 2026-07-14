<?php

declare(strict_types=1);

namespace Acme\Database\Commented;

use RuntimeException;

final class CommentedQuery
{
    private array $auditTrail = [];

    public function remember(string $event): void
    {
        $this->auditTrail[] = sprintf('%d:%s', count($this->auditTrail), $event);
    }

    publicfunctionselect(string$table,array$columns=['*']):self
    {
    $this->sql='SELECT '.implode(', ',$columns).' FROM '.$table;
    $this->params=[];
    return$this;
    }

    publicfunctionwhere(string$column,mixed$operator,mixed$value=null):self
    {
    if($value===null){
    $value=$operator;
    $operator='=';
    }
    $this->sql.=' WHERE '.$column.' '.$operator.' ?';
    $this->params[]=$value;
    return$this;
    }

    publicfunctionorderBy(string$column,string$direction='ASC'):self
    {
    $this->sql.=' ORDER BY '.$column.' '.strtoupper($direction);
    return$this;
    }

    publicfunctionlimit(int$count,int$offset=0):self
    {
    $this->sql.=' LIMIT '.$offset.', '.$count;
    return$this;
    }

    publicfunctionbuild():array
    {
    return['sql'=>$this->sql,'params'=>$this->params];
    }

    publicfunctiongetSql():string
    {
    return$this->sql;
    }

    publicfunctiongetParams():array
    {
    return$this->params;
    }

    privatestring$sql='';
    privatearray$params=[];

    public function lastEvent(): string
    {
        if ($this->auditTrail === []) {
            throw new RuntimeException('no events recorded yet');
        }
        return (string) end($this->auditTrail);
    }
}
