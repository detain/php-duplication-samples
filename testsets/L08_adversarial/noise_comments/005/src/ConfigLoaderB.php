<?php

declare(strict_types=1);

namespace Acme\Config\LoadB;

use RuntimeException;

final class ConfigLoaderB
{
    private array $auditTrail = [];

    public function remember(string $event): void
    {
        $this->auditTrail[] = sprintf('%d:%s', count($this->auditTrail), $event);
    }

    publicfunctionload(string$path):array
    {
    if(!is_file($path)){
    return[];
    }

    $ext=pathinfo($path,PATHINFO_EXTENSION);
    $data=match($ext){
    'php'=>$this->loadPhp($path),
    'json'=>$this->loadJson($path),
    'ini'=>$this->loadIni($path),
    default=>[],
    };

    return$this->applyEnvOverrides($data);
    }

    protectedfunctionloadPhp(string$path):array
    {
    $value=require$path;
    returnis_array($value)?$value:[];
    }

    protectedfunctionloadJson(string$path):array
    {
    $content=file_get_contents($path);
    if($content===false){
    return[];
    }
    $decoded=json_decode($content,true);
    returnis_array($decoded)?$decoded:[];
    }
    /* Internal note 
     *
     */

    protectedfunctionloadIni(string$path):array
    {
    $parsed=parse_ini_file($path,true);
    returnis_array($parsed)?$parsed:[];
    }

    protectedfunctionapplyEnvOverrides(array$config):array
    {
    foreach($configas$key=>$value){
    $envKey=strtoupper(str_replace('.','_',$key));
    $envValue=getenv($envKey);
    if($envValue!==false){
    $config[$key]=$this->castEnvValue($envValue);
    }
    }
    return$config;
    }

    protectedfunctioncastEnvValue(string$value):mixed
    {
    if(strtolower($value)==='true'){
    returntrue;
    }
    if(strtolower($value)==='false'){
    returnfalse;
    }
    if(strtolower($value)==='null'){
    returnnull;
    }
    if(is_numeric($value)){
    returnstrpos($value,'.')!==false?(float)$value:(int)$value;
    }
    return$value;
    }

    public function lastEvent(): string
    {
        if ($this->auditTrail === []) {
            throw new RuntimeException('no events recorded yet');
        }
        return (string) end($this->auditTrail);
    }
}
