<?php
require_once('../includes/OracleCieloJornada.class.php');
require_once('../includes/Controles.class.php');
require_once('../includes/execute.class.php');

$OraCielo   = new OracleCielo();
$conexaoOra = $OraCielo->getCon();
$execute    = new ExecClass($conexaoOra);
$CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexaoOra);
$empresaUsu = $CtrlAcesso->getUserEmpresa($_SESSION);

function getTimeHr($dtIni, $dtFim){
    $time1                  = $dtIni;
    $time2                  = $dtFim;
    list($hours, $minutes)  = explode(':', $time1);
    $startTimestamp         = mktime($hours, $minutes);

    list($hours, $minutes)  = explode(':', $time2);
    $endTimestamp           = mktime($hours, $minutes);

    $seconds                = $endTimestamp - $startTimestamp;
    $minutes                = ($seconds / 60) % 60;
    $hours                  = intval($seconds / (60 * 60));
    #echo '<br>----> =>'.$dtIni.'-'.$dtFim.' = '.$seconds;
    $min                    = ($minutes < 10)?'0'.$minutes:$minutes;
    return '('.$hours.':'.$min.')';
}

function getTime($dtIni, $dtFim){
    $time1                  = $dtIni;
    $time2                  = $dtFim;
    list($hours, $minutes)  = explode(':', $time1);
    $startTimestamp         = mktime($hours, $minutes);

    list($hours, $minutes)  = explode(':', $time2);
    $endTimestamp           = mktime($hours, $minutes);

    $seconds                = $endTimestamp - $startTimestamp;
    return $seconds;
}

function getHr($min){
    #$minutes = ($sec / 60) % 60;
    $minutes = round($min % 60);
    $hours   = intval($min / 60);
    $hours   = ($hours < 10)?'0'.$hours:$hours;
    $minutes = ($minutes < 10)?'0'.$minutes:$minutes;
    return ' ('.$hours.':'.$minutes.')';
}

$sqlEmpUsu  = (!$empresaUsu)?'':'AND cond.empresa = '.$empresaUsu;
$sqlDtIni   = $_REQUEST['dtIni'];
$sqlDtFim   = $_REQUEST['dtFim'];
$sqlHoraIni = $_REQUEST['horaIni'];
$sqlHoraFim = $_REQUEST['horaFim'];

if($_REQUEST['horaIni'] === '' || $_REQUEST['horaIni'] === null && $_REQUEST['horaFim'] == '' || $_REQUEST['horaFim'] == null){
    $sqlHoras = "";
}else{
    $sqlHoras = " 
            AND to_char(jor.data_ini, 'hh24:mi') >= '$sqlHoraIni'
            AND to_char(jor.data_fim, 'hh24:mi') <= '$sqlHoraFim'";
}

$sqlCartaoPonto = "
        SELECT 
            sit.id_situacao, 
            cond.id_condutor, 
            cond.nome AS cond, 
            jor.placa AS plc,
            jor.data_fim-jor.data_ini,
            (EXTRACT(HOUR FROM jor.data_fim-jor.data_ini)*60*60) + (EXTRACT(MINUTE FROM jor.data_fim-jor.data_ini)*60) + EXTRACT(SECOND FROM jor.data_fim-jor.data_ini) 
            AS tempo_sec,
            (EXTRACT(HOUR FROM jor.data_fim-jor.data_ini)*60) + EXTRACT(MINUTE FROM jor.data_fim-jor.data_ini) 
            AS tempo,
            ' ('||trim(to_char(EXTRACT(HOUR FROM jor.data_fim-jor.data_ini), '09'))||':'||TRIM(to_char(EXTRACT(MINUTE FROM jor.data_fim-jor.data_ini), '00'))||')'
            AS tempoHrMin,
            sit.descricao AS desc_situacao,
            to_char(jor.data_ini, 'hh24:mi') AS dt_ini, 
            CASE WHEN trunc(jor.data_ini) < trunc(jor.data_fim)
            --THEN '<b>'||to_char(jornada.data_fim, 'hh24:mi')||'<sup>+'||(trunc(data_fim)-trunc(data_ini))||' dia</sup></b>'
            THEN '<br><b>'||to_char(jor.data_fim, 'DD/MM hh24:mi')||'</b>'
            ELSE to_char(jor.data_fim, 'hh24:mi')
            END  AS dt_fim,
            jor.data_ini, 
            jor.data_fim, 
            to_char(jor.data_ini, 'DD/MM') AS dt,
            to_char(jor.data_ini, 'YYYY-MM-DD') AS dt_diario,	
            CASE WHEN to_char(jor.data_ini, 'D') = '1'
            THEN 'Domingo'
            WHEN to_char(jor.data_ini, 'D') = '2'
            THEN 'Segunda'
            WHEN to_char(jor.data_ini, 'D') = '3'
            THEN 'Ter&ccedil;a'
            WHEN to_char(jor.data_ini, 'D') = '4'
            THEN 'Quarta'
            WHEN to_char(jor.data_ini, 'D') = '5'
            THEN 'Quinta'
            WHEN to_char(jor.data_ini, 'D') = '6'
            THEN 'Sexta'
            WHEN to_char(jor.data_ini, 'D') = '7'
            THEN 'S&aacute;bado'
            END AS dia,
            to_char(jor.data_ini, 'D') as num_dia,
            CASE WHEN jor.editado = 'T'
            THEN 'T'
            ELSE 'F'
            END AS justif,
            CASE WHEN jor.afastamento IS NULL
            THEN 'F'
            ELSE 'T'
            END AS afastamento,
            CASE WHEN jor.afastamento IS NULL
            THEN 0 
            ELSE jor.afastamento
            END AS id_afastamento,
            mot.descricao AS desc_afast,
            mot.id_motivo AS id_motivo_afast,
            to_char(afast.data_ini, 'DD/MM/YYYY') AS data_ini_afast,
            to_char(afast.data_fim, 'DD/MM/YYYY') AS data_fim_afast
        FROM  monitoramento.condutor cond, jornada.situacao sit, jornada.jornada jor
        LEFT JOIN jornada.afastamento afast ON jor.afastamento = afast.id_afastamento 
        LEFT JOIN jornada.afast_motivo mot ON afast.motivo = mot.id_motivo
        WHERE jor.condutor = id_condutor
            AND jor.situacao = id_situacao
            AND jor.situacao IN (4, 5, 6, 7, 8, 9, 10)
            AND TRUNC(jor.data_ini) >= to_date('".$sqlDtIni."', 'DDMMYYYY') 
            AND TRUNC(jor.data_ini) <= to_date('".$sqlDtFim."', 'DDMMYYYY')
            AND cond.id_condutor = ".$_REQUEST['idCondutor']."
            ".$sqlEmpUsu."
            ".$sqlHoras."
        ORDER BY jor.data_ini, jor.data_fim";
#echo $sqlCartaoPonto; exit();

$respostaCP         = OCIParse($conexaoOra, $sqlCartaoPonto);
$respostaExecucao   = OCIExecute($respostaCP);

$totalJornada   = 0;
$tempoAux       = 0;
$tmpJornada     = 0;
$tmpHrExtra     = 0;
$tmpExtra100    = 0;
$tmpEspera      = 0;
$auxAfast       = 0;
$auxIdMotAfast  = 0;
$tmpNoturno     = 0;
$countDiasAfastamento = 0;

$idAux          = '';
$idAuxTmp       = '';
$dataAux        = '';
$dtIniAux       = '';
$dtFimAux       = '';
$justificativa  = 'F';
$afastamento    = 'F';
$descAfast      = '';
$repAux         = 'F';
$periodoAfast   = '';
$arrDia         = '';

$rows           = array();
$arrData        = array();

if($respostaExecucao == null){
    $status = "ERROR";
}else{
    $status = "OK";
}

/*
 * 4   -> REPOUSO
 * 5   -> JORNADA
 * 6   -> REFEICAO
 * 7   -> DESCANSO
 * 8   -> ESPERA
 * 9   -> EXTRA
 * 10  -> EXTRA100%
*/
if($status == "OK"){
    while(OCIFetchInto($respostaCP, $rowDb, OCI_ASSOC)){

        $idAux = $rowDb['ID_SITUACAO'];

        if($dataAux <> $rowDb['DT']){

            if($afastamento <> 'T'){

                if($tmpJornada > 0){
                    $index              = count( $arrJornada ) - 1;
                    $arrJornada[$index] = $arrJornada[$index].' '.getHr($tmpJornada);
                    $tmpJornada         = 0;
                    $repAux             = 'F';
                }

                if($dataAux <> ''){
                    $rows[] = array(
                        "idCondutor"    => $rowDb['ID_CONDUTOR'],
                        "nmCondutor"    => htmlentities($rowDb['COND']),
                        "diarioDt"      => implode('<br>',array_values($arrDtDiario)),
                        "Data"          => implode('<br>',array_values($arrData)),
                        "Dia"           => $arrDia,
                        "Repouso"       => (implode('<br>',array_values($arrRepouso))== ''?'(00:00)':implode('<br>',array_values($arrRepouso))),
                        "Jornada"       => (implode('<br>',array_values($arrJornada))== ''?'(00:00)':implode('<br>',array_values($arrJornada))),//.getHr($tmpJornada),
                        "HoraExtra"     => implode('<br>',array_values($arrHrExtra)).getHr($tmpHrExtra),
                        "HrExtra100"    => implode('<br>',array_values($arrExtra100)).getHr($tmpExtra100),
                        "Refeicao"      => (implode('<br>',array_values($arrRefeicao))== ''?'(00:00)':implode('<br>',array_values($arrRefeicao))),
                        "Descanso"      => (implode('<br>',array_values($arrDescanso))== ''?'(00:00)':implode('<br>',array_values($arrDescanso))),
                        "Espera"        => implode('<br>',array_values($arrEspera)).getHr($tmpEspera),
                        "Justif"        => $justificativa,
                        "Afastamento"   => $afastamento,
                        "DescAfast"     => $descAfast,
                        "TmpJornada"    => $tmpJornada,
                        "TotalJornada"  => $totalJornada,
                        "TmpHrExtra"    => $tmpHrExtra,
                        "TmpExtra100"   => $tmpExtra100,
                        "TmpEspera"     => $tmpEspera,
                        "TempHrNoturna" => $tmpNoturno
                    );
                }

                $arrData        = array();
                $arrDia         = array();
                $arrDtDiario    = array();
                $arrRepouso     = array();
                $arrJornada     = array();
                $arrRefeicao    = array();
                $arrDescanso    = array();
                $arrEspera      = array();
                $arrHrExtra     = array();
                $arrExtra100    = array();

                $tempoAux       = 0;
                $tmpJornada     = 0;
                $tmpHrExtra     = 0;
                $tmpExtra100    = 0;
                $tmpEspera      = 0;
                $tmpNoturno     = 0;

                $justificativa  = 'F';
                $afastamento    = 'F';
                $descAfast      = '';
                $periodoAfast   = '';
                $idAuxTmp       = '';

                $dataAux        = $rowDb['DT'];
                $arrDtDiario[]  = $rowDb['DT_DIARIO'];
                $arrData[]      = $rowDb['DT'].' - '.$rowDb['DIA'];
                $arrDia         = $rowDb['NUM_DIA'];
                $auxAfast       = $rowDb['ID_AFASTAMENTO'];
                $auxIdMotAfast  = $rowDb['ID_MOTIVO_AFAST'];
            }else{

                if($tmpJornada > 0){
                    $index              = count( $arrJornada ) - 1;
                    $arrJornada[$index] = $arrJornada[$index].' '.getHr($tmpJornada);
                    $tmpJornada         = 0;
                    $repAux             = 'F';
                }

                #Testo se o id_afastamento anterior é diferente de 0, o que indica que é um afastamento e testo se o novo é diferente do anterior,
                #o que indica o fim do afastamento, portanto, o último dia de afastamento será criado e então este dia será manipulado com as informações abaixo.
                if($auxAfast <> 0 and $rowDb['ID_AFASTAMENTO'] <> $auxAfast){
                    if($dataAux <> ''){
                        $rows[] = array(
                            "idCondutor"    => '',
                            "nmCondutor"    => '',
                            "diarioDt"      => '',
                            "Data"          => $periodoAfast,
                            "Repouso"       => $descAfast,
                            "Jornada"       => $descAfast,
                            "HoraExtra"     => $descAfast,
                            "HrExtra100"    => $descAfast,
                            "Refeicao"      => $descAfast,
                            "Descanso"      => $descAfast,
                            "Espera"        => $descAfast,
                            "Justif"        => 'F',
                            "Afastamento"   => $afastamento,
                            "DescAfast"     => $descAfast,
                            "TmpJornada"    => 0,
                            "TotalJornada"  => 0,
                            "TmpHrExtra"    => 0,
                            "TmpExtra100"   => 0,
                            "TmpEspera"     => 0,
                            "TempHrNoturna" => 0
                        );
                    }
                }

                $arrData        = array();
                $arrDtDiario    = array();
                $arrRepouso     = array();
                $arrJornada     = array();
                $arrRefeicao    = array();
                $arrDescanso    = array();
                $arrEspera      = array();
                $arrHrExtra     = array();
                $arrExtra100    = array();

                $tempoAux       = 0;
                $tmpJornada     = 0;
                $tmpHrExtra     = 0;
                $tmpExtra100    = 0;
                $tmpEspera      = 0;
                $tmpNoturno     = 0;



                $justificativa  = 'F';
                $afastamento    = 'F';
                $descAfast      = '';
                $periodoAfast   = '';
                $idAuxTmp       = '';
                $arrDia         = '';

                $dataAux        = $rowDb['DT'];
                $arrDtDiario[]  = $rowDb['DT_DIARIO'];
                $arrData[]      = $rowDb['DT'].' - '.$rowDb['DIA'];
                $arrDia         = $rowDb['NUM_DIA'];
                $auxAfast       = $rowDb['ID_AFASTAMENTO'];
                $auxIdMotAfast  = $rowDb['ID_MOTIVO_AFAST'];
            }
        }

        /*
         * 4   -> REPOUSO
         * 5   -> JORNADA
         * 6   -> REFEICAO
         * 7   -> DESCANSO
         * 8   -> ESPERA
         * 9   -> EXTRA
         * 10  -> EXTRA100%
        */
        if($afastamento == 'F') {

            if ($idAuxTmp <> $idAux) {

                if ($rowDb['JUSTIF'] == 'T') {
                    $justificativa = 'T';
                }

                if ($rowDb['AFASTAMENTO'] == 'T') {
                    $afastamento = 'T';

                    switch ($rowDb['ID_MOTIVO_AFAST']){
                        case 1:
                            #Atestado
                            $countAtestado++;
                            break;
                        case 2:
                            #Ferias
                            $countFerias++;
                            break;
                        case 3:
                            #Afastamento
                            $countAfastamento++;
                            break;
                        default:
                            break;
                    }

                    $descAfast = $rowDb['DESC_AFAST'];
                    if ($rowDb['DATA_INI_AFAST'] <> $rowDb['DATA_FIM_AFAST']) {
                        $periodoAfast = $rowDb['DATA_INI_AFAST'] . ' até ' . $rowDb['DATA_FIM_AFAST'];
                    } else {
                        $periodoAfast = $rowDb['DT'] . ' - ' . $rowDb['DIA'];
                    }
                }

                $tmp = ($rowDb['DT_INI'] and $rowDb['DT_FIM']) ? $rowDb['TEMPOHRMIN'] : '';
                $tmpSec = $rowDb['TEMPO'];

                switch ($idAux) {
                    case 4:
                        if (count($arrJornada) > 0 and $tmpJornada > 0) {
                            $index = count($arrJornada) - 1;
                            $arrJornada[$index] = $arrJornada[$index] . ' ' . getHr($tmpJornada);
                            $tmpJornada = 0;
                            $repAux = 'F';
                        }

                        $repAux = 'T';
                        $arrRepouso[] = $rowDb['DT_INI'] . '-' . $rowDb['DT_FIM'] . $tmp;
                        break;
                    case 5:

                        $arrJornada[] = $rowDb['DT_INI'] . '-' . $rowDb['DT_FIM']; //.' '.$tmp;
                        $tmpJornada = $tmpJornada + $tmpSec;
                        $totalJornada = $totalJornada + $tmpSec;
                        break;
                    case 6:
                        $arrRefeicao[] = $rowDb['DT_INI'] . '-' . $rowDb['DT_FIM'] . $tmp;
                        break;
                    case 7:
                        $arrDescanso[] = $rowDb['DT_INI'] . '-' . $rowDb['DT_FIM'] . $tmp;
                        break;
                    case 8:
                        $arrEspera[] = $rowDb['DT_INI'] . '-' . $rowDb['DT_FIM']; //.' '.$tmp;
                        $tmpEspera = $tmpEspera + $tmpSec;
                        break;
                    case 9:
                        $arrHrExtra[] = $rowDb['DT_INI'] . '-' . $rowDb['DT_FIM']; //.' '.$tmp;
                        $tmpHrExtra = $tmpHrExtra + $tmpSec;
                        break;
                    case 10:
                        $arrExtra100[] = $rowDb['DT_INI'] . '-' . $rowDb['DT_FIM']; //.' '.$tmp;
                        $tmpExtra100 = $tmpExtra100 + $tmpSec;
                        break;
                }

                //Controle de jornada noturna
                if($idAux == 5 or $idAux == 9 or $idAux == 10){
                    if ($rowDb['DT_INI'] <> null and $rowDb['DT_FIM'] <> null) {
                        $tmpNoturno += $execute->totalHorasNoturnas($rowDb['DT_INI'], $rowDb['DT_FIM']);
                    }else{
                        #Se cair no else, deve-se regerar a jornada pois alguma situacao não está ok
                    }
                }

                $dtIniAux = $rowDb['DT_INI'];
                $dtFimAux = $rowDb['DT_FIM'];
                $idAuxTmp = $idAux;
            } else {
                $tmp = ($dtIniAux and $rowDb['DT_FIM']) ? $rowDb['TEMPOHRMIN'] : '';
                $tmpSec = $rowDb['TEMPO'];

                switch ($idAux) {
                    case 4:
                        if (count($arrJornada) > 0 and $tmpJornada > 0) {
                            $index = count($arrJornada) - 1;
                            $arrJornada[$index] = $arrJornada[$index] . ' ' . getHr($tmpJornada);
                            $tmpJornada = 0;
                            $repAux = 'F';
                        }

                        $repAux = 'T';
                        $index = count($arrRepouso) - 1;
                        $arrRepouso[$index] = $dtIniAux . '-' . $rowDb['DT_FIM'] . $tmp;

                        break;
                    case 5:
                        $index = count($arrJornada) - 1;
                        $arrJornada[$index] = $dtIniAux . '-' . $rowDb['DT_FIM'];//.' '.$tmp;
                        $tmpJornada = $tmpJornada + $tmpSec;
                        $totalJornada = $totalJornada + $tmpSec;
                        break;
                    case 6:
                        $index = count($arrRefeicao) - 1;
                        $arrRefeicao[$index] = $dtIniAux . '-' . $rowDb['DT_FIM'] . $tmp;
                        break;
                    case 7:
                        $index = count($arrDescanso) - 1;
                        $arrDescanso[$index] = $dtIniAux . '-' . $rowDb['DT_FIM'] . $tmp;
                        break;
                    case 8:
                        $index = count($arrEspera) - 1;
                        $tmpEspera = $tmpEspera + $tmpSec;
                        $arrEspera[$index] = $dtIniAux . '-' . $rowDb['DT_FIM'];//.' '.$tmp;
                        break;
                    case 9:
                        $index = count($arrHrExtra) - 1;
                        $tmpHrExtra = $tmpHrExtra + $tmpSec;
                        $arrHrExtra[$index] = $dtIniAux . '-' . $rowDb['DT_FIM'];//.' '.$tmp;
                        break;
                    case 10:
                        $index = count($arrExtra100) - 1;
                        $tmpExtra100 = $tmpExtra100 + $tmpSec;
                        $arrExtra100[$index] = $dtIniAux . '-' . $rowDb['DT_FIM'];//.' '.$tmp;
                        break;
                }

                //Controle de jornada noturna
                if($idAux == 5 or $idAux == 9 or $idAux == 10){
                    if ($row['DT_INI'] <> null and $row['DT_FIM'] <> null) {
                        $tmpNoturno += $execute->totalHorasNoturnas($row['DT_INI'], $row['DT_FIM']);
                    }else{
                        #Se cair no else, deve-se regerar a jornada pois alguma situacao não está ok
                    }
                }
            }
        }
    }

    if($tmpJornada > 0){
        $index              = count( $arrJornada ) - 1;
        $arrJornada[$index] = $arrJornada[$index].' '.getHr($tmpJornada);
        $tmpJornada         = 0;
        $repAux             = 'F';
    }

    if($arrData){
        $rows[] = array(
            "idCondutor"    => $rowDb['ID_CONDUTOR'],
            "diarioDt"      => implode('<br>',array_values($arrDtDiario)),
            "Data"          => ($periodoAfast == '' or $periodoAfast == null)?implode('<br>',array_values($arrData)):$periodoAfast,
            "Dia"           => $arrDia,
            "Repouso"       => (implode('<br>',array_values($arrRepouso))== ''?'(00:00)':implode('<br>',array_values($arrRepouso))),
            "Jornada"       => (implode('<br>',array_values($arrJornada))== ''?'(00:00)':implode('<br>',array_values($arrJornada))),
            "HoraExtra"     => implode('<br>',array_values($arrHrExtra)).getHr($tmpHrExtra),
            "HrExtra100"    => implode('<br>',array_values($arrExtra100)).getHr($tmpExtra100),
            "Refeicao"      => (implode('<br>',array_values($arrRefeicao))== ''?'(00:00)':implode('<br>',array_values($arrRefeicao))),
            "Descanso"      => (implode('<br>',array_values($arrDescanso))== ''?'(00:00)':implode('<br>',array_values($arrDescanso))),
            "Espera"        => implode('<br>',array_values($arrEspera)).getHr($tmpEspera),
            "Justif"        => $justificativa,
            "Afastamento"   => $afastamento,
            "DescAfast"     => $descAfast,
            "TmpJornada"    => $tmpJornada,
            "TotalJornada"  => $totalJornada,
            "TmpHrExtra"    => $tmpHrExtra,
            "TmpExtra100"   => $tmpExtra100,
            "TmpEspera"     => $tmpEspera,
            "TempHrNoturna" => $tmpNoturno
        );
    }

    //Afastamentos
    $afastamentos[] = array(
        "tmpAfastAtestado"      => $countAtestado*1440,
        "tmpAfastFerias"        => $countFerias*1440,
        "tmpAfastAfastamento"   => $countAfastamento*1440
    );
}

oci_free_statement($respostaCP);
oci_close($conexaoOra);

$array['cartaoPonto']       = $rows;
$array['status']            = $status;
$array['afastamentos']      = $afastamentos;
$json                       = $array;
$callback                   = $_REQUEST['jsonp'];
#print_r($json);

//start output
if ($callback) {
    header('Content-Type: text/javascript');
    echo $callback . '(' . json_encode($json) . ');';
} else {
    header('Content-Type: application/x-json');
    echo json_encode($json);
}