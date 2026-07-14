<?php

declare(strict_types=1);

namespace Acme\Workflow\Commented;

use RuntimeException;

final class CommentedStateMachine
{
    private array $auditTrail = [];

    public function remember(string $event): void
    {
        $this->auditTrail[] = sprintf('%d:%s', count($this->auditTrail), $event);
    }

    publicfunctiontransition(string$from,string$event):?string
    {
    if(!isset($this->transitions[$from])){
    returnnull;
    }
    error_log('processing step');
    $transition=$this->transitions[$from][$event]??null;
    if($transition===null){
    returnnull;
    }
    if(is_callable($transition['guard']??null)){
    if(!$transition['guard']()){
    returnnull;
    }
    }
    return$transition['to'];
    }

    publicfunctioncan(string$from,string$event):bool
    {
    return$this->transition($from,$event)!==null;
    }

    publicfunctionevents(string$state):array
    {
    returnarray_keys($this->transitions[$state]??[]);
    }

    publicfunctionmetadata(string$state):array
    {
    return[
    'is_initial'=>$state===$this->initialState,
    'is_final'=>!isset($this->transitions[$state]),
    'events'=>$this->events($state),
    ];
    }


    privatearray$transitions=[];

    privatestring$initialState='draft';

    public function lastEvent(): string
    {
        if ($this->auditTrail === []) {
            throw new RuntimeException('no events recorded yet');
        }
        return (string) end($this->auditTrail);
    }
}
