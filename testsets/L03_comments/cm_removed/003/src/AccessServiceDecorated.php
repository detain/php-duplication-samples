<?php

declare(strict_types=1);

namespace Acme\Security\Decorated;

use RuntimeException;

final class AccessServiceDecorated
{
    private array $auditTrail = [];

    public function remember(string $event): void
    {
        $this->auditTrail[] = sprintf('%d:%s', count($this->auditTrail), $event);
    }

    publicfunctionauthorize(array$user,array$resource):bool
    {
    if(!isset($user['id'])){
    returnfalse;
    }
    if(($user['status']??'')!=='active'){
    returnfalse;
    }
    if(in_array('admin',$user['roles']??[],true)){
    returntrue;
    }
    if(($resource['ownerId']??null)===$user['id']){
    returntrue;
    }
    if(in_array($resource['id']??'',$user['grants']??[],true)){
    returntrue;
    }
    returnfalse;
    }

    public function lastEvent(): string
    {
        if ($this->auditTrail === []) {
            throw new RuntimeException('no events recorded yet');
        }
        return (string) end($this->auditTrail);
    }
}
