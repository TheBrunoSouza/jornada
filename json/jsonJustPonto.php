<?php
require_once('../includes/OracleCieloJornada.class.php');
require_once('../includes/Controles.class.php');

$OraCielo   = new OracleCielo();
$conexaoOra = $OraCielo->getCon();
$CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexao);
$empresaUsu = $CtrlAcesso->getUserEmpresa($_SESSION);

$exp            = explode('-', $_REQUEST['dtEdt']);
$dataOracle     = $exp[2].$exp[1].$exp[0];
$controle       = "";
$sqlAlteracoes  = "";

if($_REQUEST['acao'] == 'consultaAlteracoes'){
    $sqlConsultaAlteracoes = "
        SELECT DISTINCT to_char(data_alt, 'DD/MM/YYYY hh24:mi:ss') as alteracao
        FROM jornada_log
        WHERE condutor = '".$_REQUEST['idCondutor']."'
            AND data_alt IS NOT NULL
            AND trunc(data_ini) >= to_date('".$dataOracle."', 'DDMMYYYY')
            AND trunc(data_fim) <= to_date('".$dataOracle."', 'DDMMYYYY')
        ORDER BY alteracao";

    $respostaConsAlteracoes = oci_parse($conexaoOra, $sqlConsultaAlteracoes);
    
    if(!oci_execute($respostaConsAlteracoes)){
        echo ' Erro no select da jornada log - alteracoes.';
        echo $sqlConsultaAlteracoes;
        exit();
    }else{
        while (($row = oci_fetch_assoc($respostaConsAlteracoes)) != false) {
            $arrayAlteracoes[] = array(
                "dataAlteracao" => $row['ALTERACAO']
            );
        }
    }    
    $array['alteracoes'] = $arrayAlteracoes;
    oci_free_statement($respostaConsAlteracoes);
}else{
    //Defini se o select sera para o log anterior ou atual
    switch ($_REQUEST['momento']){
        case 'antes':
            $controle = "IS NOT NULL";
            break;
        case 'depois':
            $controle = "IS NULL";
            break;
    }
    
    if($_REQUEST['dataAlteracao'] != ''){
        $sqlAlteracoes = "AND to_char(jg.data_alt, 'DD/MM/YYYY hh24:mi:ss') = '".$_REQUEST['dataAlteracao']."'";
    }else{
        $sqlAlteracoes = "AND jg.data_alt = (SELECT max(jg.data_alt)
                                             FROM   jornada_log jg
                                             WHERE  TRUNC(jg.data_ini) = to_date('".$dataOracle."', 'DDMMYYYY')
                                                    AND jg.condutor = '".$_REQUEST['idCondutor']."'
                                                    AND jg.descricao IS NULL)";
    }
    
    $sqlLogJornada = "
        SELECT  jg.id_jornada_log,
                s.descricao as desc_situacao,
                to_char(jg.data_ini, 'hh24:mi') as hr_ini, 
                to_char(jg.data_fim, 'hh24:mi') as hr_fim,
                (EXTRACT(HOUR FROM jg.data_fim-jg.data_ini)*60) + EXTRACT(MINUTE FROM jg.data_fim-jg.data_ini) AS tempo_sec,
                getintervaltochar(jg.data_ini, jg.data_fim) as tempo,
                to_char(jg.data_db, 'DD/MM/YYYY') AS data_db,
                to_char(jg.data_hora, 'DD/MM/YYYY hh24:mi') AS data_hora,
                jg.condutor,
                jg.placa,
                to_char(jg.data_alt, 'DD/MM/YYYY') AS data_alt,
                jg.usuario as id_usuario,
                us.nome as nome_usuario,
                jg.sit_anterior,
                jg.jornada_ant,
                jg.descricao
        FROM    jornada_log jg, situacao s, usuario us
        WHERE   TRUNC(jg.data_ini) = to_date('".$dataOracle."', 'DDMMYYYY')
                AND jg.condutor = '".$_REQUEST['idCondutor']."'
                AND jg.descricao ".$controle."
                ".$sqlAlteracoes."
                AND s.id_situacao = jg.situacao
                AND jg.usuario = us.id_usuario
        ORDER BY jg.data_ini, jg.data_fim";

    $respostaLogJornada = oci_parse($conexaoOra, $sqlLogJornada);

    if(!oci_execute($respostaLogJornada)){
        echo ' Status SELECT JORNADA_LOG: ERRO ->'.$sqlLogJornada;
        exit();
    }else{
//        echo ' Status SELECT JORNADA_LOG: OK->'.$sqlLogJornada;
//        exit();

        $tempoJornada   = 0;
        $tempoEspera    = 0;
        $tempoExtra     = 0;
        $tempoExtra100  = 0;

        while (($row = oci_fetch_assoc($respostaLogJornada)) != false) {

            switch ($row['DESC_SITUACAO']){
                case 'Jornada':
                    $tempoSituacao  = $row['TEMPO_SEC'];
                    $tempoJornada   = $tempoJornada + $tempoSituacao;
                    break;
                case 'Espera':
                    $tempoSituacao  = $row['TEMPO_SEC'];
                    $tempoEspera    = $tempoEspera + $tempoSituacao;
                    break;
                case 'Hora Extra':
                    $tempoSituacao  = $row['TEMPO_SEC'];
                    $tempoExtra     = $tempoExtra + $tempoSituacao;
                    break;
                case 'Hora Extra 100%':
                    $tempoSituacao  = $row['TEMPO_SEC'];
                    $tempoExtra100  = $tempoExtra100 + $tempoSituacao;
                    break;
            }

            $arrayLogJornada[] = array(
                "idJornadaLog"          => $row['ID_JORNADA_LOG'],
                "descricaoSituacao"     => $row['DESC_SITUACAO'],
                "dataIni"               => $row['HR_INI'],
                "dataFim"               => $row['HR_FIM'],
                "tempo"                 => $row['TEMPO'],
                "dataDb"                => $row['DATA_DB'],
                "dataHora"              => $row['DATA_HORA'],
                "idCondutor"            => $row['CONDUTOR'],
                "placa"                 => $row['PLACA'],
                "dataAlteracao"         => $row['DATA_ALT'],
                "idUsuario"             => $row['ID_USUARIO'],
                "nomeUsuario"           => $row['NOME_USUARIO'],
                "idSituacaoAnterior"    => $row['SIT_ANTERIOR'],
                "idJornadaAnterior"     => $row['JORNADA_ANT'],
                "descricao"             => $row['DESCRICAO']
            );
            $minutos = 0;
        }

        $array['logJornada']    = $arrayLogJornada;
        $array['totalJornada']  = $tempoJornada;
        $array['totalEspera']   = $tempoEspera;
        $array['totalExtra']    = $tempoExtra;
        $array['totalExtra100'] = $tempoExtra100;
    }
    oci_free_statement($respostaLogJornada);
}

oci_close($conexaoOra);

$json = $array;

//start output
//if ($callback) {
//    header('Content-Type: text/javascript');
//    echo $callback . '(' . json_encode($json) . ');';
//} else {
    header('Content-Type: application/x-json');
    echo json_encode($json);
//}