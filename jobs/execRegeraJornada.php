<?php
session_start();

require_once('../includes/OracleCieloJornada.class.php');
require_once('../includes/Controles.class.php');
require_once('../includes/execute.class.php');

$OraCielo       = new OracleCielo();
$conexaoOra     = $OraCielo->getCon();
$ExecClass      = new ExecClass($conexaoOra);
$CtrlAcesso     = new Controles($_SERVER['REMOTE_ADDR'], $conexaoOra);
$idUsuario      = $CtrlAcesso->getUserID($_SESSION);
$empCond        = $CtrlAcesso->getUserEmpresa($_SESSION);
$condutor       = $_REQUEST['condutor'];
$exp            = explode('-', $_REQUEST['dataGeracao']);
$dataOracle     = $exp[2].$exp[1].$exp[0];

//Consultando se existe algum registro de log
//Sim: Grava novo log
//Não: Apenas regera a jornada do condutor
$sqlVerificaLog = "SELECT count(*) as numeroDeLog FROM jornada_log WHERE condutor = '".$condutor."' AND trunc(data_ini) = to_date('".$dataOracle."', 'DDMMYYYY')";
$resLog = oci_parse($conexaoOra, $sqlVerificaLog);

if(!oci_execute($resLog)){
    echo ' Erro no select de consulta do do log.';
    echo $sqlVerificaLog;
    exit();
}else{
    $log = oci_fetch_assoc($resLog);
    if($log['NUMERODELOG'] > 0){

        //Capturando a hora do sistema para incluir no log
        $horaDaAlteracao = $ExecClass->getHoraSistema();

        //Grava a jornada atual
        $ExecClass->gravaLogJornada($dataOracle, $condutor, $idUsuario, "Dados regerados.", $horaDaAlteracao['DATA_SISTEMA']);

        //Regera a jornada
        $ExecClass->regeraJornada($condutor, $empCond, $dataOracle);

        //Grava a jornada regerada
        $ExecClass->gravaLogJornada($dataOracle, $condutor, $idUsuario, NULL, $horaDaAlteracao['DATA_SISTEMA']);

        //Atualiza a coluna de edição para 'T'
        $ExecClass->atualizaEditado($condutor, $dataOracle);
    }else{
        //Regera a jornada
        $ExecClass->regeraJornada($condutor, $empCond, $dataOracle);
    }
}

oci_free_statement($resLog);
oci_close($conexaoOra);