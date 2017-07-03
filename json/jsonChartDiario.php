<?php
require_once('../includes/OracleCieloJornada.class.php');

$OraCielo   = new OracleCielo();
$conexao    = $OraCielo->getCon();

$colors     = array();
$table      = array();

$table['cols'] = array(
    array('label' => 'Situacao', 'type' => 'string'),
    array('label' => 'Descrição', 'type' => 'string'),
    array('label' => 'Start', 'type' => 'datetime'),
    array('label' => 'End', 'type' => 'datetime')
);

$rows           = array();
$tmpId          = array();
$dataDefault    = new DateTime('00:00');

$tempRep    = array();
$tempRep[]  = array('v' => 'Repouso');
$tempRep[]  = array('v' => 'Repouso');
$tempRep[]  = array('v' => $dataDefault->format('H:i'));
$tempRep[]  = array('v' => $dataDefault->format('H:i'));

$tempJor    = array();
$tempJor[]  = array('v' => 'Jornada');
$tempJor[]  = array('v' => 'Jornada');
$tempJor[]  = array('v' => $dataDefault->format('H:i'));
$tempJor[]  = array('v' => $dataDefault->format('H:i'));

$tempRef    = array();
$tempRef[]  = array('v' => 'Refeição');
$tempRef[]  = array('v' => 'Refeição');
$tempRef[]  = array('v' => $dataDefault->format('H:i'));
$tempRef[]  = array('v' => $dataDefault->format('H:i'));

$tempDes    = array();
$tempDes[]  = array('v' => 'Descanso');
$tempDes[]  = array('v' => 'Descanso');
$tempDes[]  = array('v' => $dataDefault->format('H:i'));
$tempDes[]  = array('v' => $dataDefault->format('H:i'));

$tempEsp    = array();
$tempEsp[]  = array('v' => 'Espera');
$tempEsp[]  = array('v' => 'Espera');
$tempEsp[]  = array('v' => $dataDefault->format('H:i'));
$tempEsp[]  = array('v' => $dataDefault->format('H:i'));

$tempExt    = array();
$tempExt[]  = array('v' => 'Hora Extra');
$tempExt[]  = array('v' => 'Hora Extra');
$tempExt[]  = array('v' => $dataDefault->format('H:i'));
$tempExt[]  = array('v' => $dataDefault->format('H:i'));

$temp100    = array();
$temp100[]  = array('v' => 'Hora Extra 100%');
$temp100[]  = array('v' => 'Hora Extra 100%');
$temp100[]  = array('v' => $dataDefault->format('H:i'));
$temp100[]  = array('v' => $dataDefault->format('H:i'));

$aux        = 0;

/*
4   -> Repouso
5   -> Jornada
6   -> Refeição
7   -> Descanso
8   -> Espera
9   -> Hora Extra
10  -> Hora Extra 100%
*/
$sqlDb = "
        SELECT situacao.id_situacao, jornada.situacao, condutor.nome AS cond, jornada.placa AS plc, situacao.descricao AS desc_situacao,
              CASE
                  WHEN data_fim IS NOT NULL
                  THEN (EXTRACT(HOUR FROM jornada.data_fim-jornada.data_ini)*60)+EXTRACT(MINUTE FROM jornada.data_fim-jornada.data_ini)
                  ELSE (EXTRACT(HOUR FROM SYSDATE-jornada.data_ini)*60)+EXTRACT(MINUTE FROM SYSDATE-jornada.data_ini)
              END AS tempo,
              'Date('||TO_CHAR(jornada.data_ini, 'YYYY, MM, DD, hh24, mi, ss')||')' AS dt_ini,
              CASE WHEN data_fim IS NOT NULL
                  THEN 'Date('||TO_CHAR(jornada.data_fim, 'YYYY, MM, DD, hh24, mi, ss')||')'
                  ELSE 'Date('||TO_CHAR(SYSDATE, 'YYYY, MM, DD, hh24, mi, ss')||')'
              END AS dt_fim,
              jornada.data_ini,
              jornada.data_fim
        FROM jornada, monitoramento.condutor, situacao
        WHERE   jornada.condutor = ".$_REQUEST['idCondutor']."
                AND jornada.condutor = condutor.id_condutor
                AND jornada.situacao = situacao.id_situacao
                AND TRUNC(jornada.data_ini) = TRUNC(to_date('".$_REQUEST['dtRequest']."' , 'YYYY-MM-DD'))
                AND jornada.situacao IN (4, 5, 6, 7, 8, 9, 10)
                AND jornada.data_ini <= jornada.data_fim
        ORDER BY data_ini";
//echo $sqlDb;
//exit;

$respostaDbJor = oci_parse($conexao, $sqlDb);

if(!oci_execute($respostaDbJor)){
    echo ' Erro no select de consulta na Jornada.';
    echo $sqlDb;
    exit();
}else{
    while (($rowDb = oci_fetch_assoc($respostaDbJor)) != false) {
        $aux++;
        $temp = array();

        $temp[] = array("v"=>$rowDb['DESC_SITUACAO']);
        $temp[] = array("v"=>$rowDb['DESC_SITUACAO']);
        $temp[] = array("v"=>$rowDb['DT_INI']);
        $temp[] = array("v"=>$rowDb['DT_FIM']);

        $rows[] = array('c'=>$temp);

        $tempRep = ($rowDb['ID_SITUACAO']==4)?'':$tempRep;
        $tempJor = ($rowDb['ID_SITUACAO']==5)?'':$tempJor;
        $tempRef = ($rowDb['ID_SITUACAO']==6)?'':$tempRef;
        $tempDes = ($rowDb['ID_SITUACAO']==7)?'':$tempDes;
        $tempEsp = ($rowDb['ID_SITUACAO']==8)?'':$tempEsp;
        $tempExt = ($rowDb['ID_SITUACAO']==9)?'':$tempExt;
        $temp100 = ($rowDb['ID_SITUACAO']==10)?'':$temp100;
    }
}

if($tempRep <> ''){
	$colors[]   = '#FFF';
	$rows[]     = array('c'=>$tempRep);
}
if($tempJor <> ''){
	$colors[]   = '#FFF';
	$rows[]     = array('c'=>$tempJor);
}
if($tempRef <> ''){
	$colors[]   = '#FFF';
	$rows[]     = array('c'=>$tempRef);
}
if($tempDes <> ''){
	$colors[]   = '#FFF';
	$rows[]     = array('c'=>$tempDes);
}
if($tempEsp <> ''){
	$colors[]   = '#FFF';
	$rows[]     = array('c'=>$tempEsp);
}
if($tempExt <> ''){
    $colors[]   = '#FFF';
    $rows[]     = array('c'=>$tempExt);
}
if($temp100 <> ''){
    $colors[]   = '#FFF';
    $rows[]     = array('c'=>$temp100);
}

oci_free_statement($respostaDbJor);
oci_close($conexao);

$table['rows']  = $rows;
$result         = array();

$result['chart_data'] = $table;
$result['count_data'] = $aux;

$jsonTable = json_encode($result);
echo $jsonTable;

?>