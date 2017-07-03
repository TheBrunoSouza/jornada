<?php
session_start();

require_once('../includes/Controles.class.php');
require_once('../includes/OracleCieloJornada.class.php');

$OraCielo   = new OracleCielo();
$conexao    = $OraCielo->getCon();

$CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexao);

$idUsuario  = $CtrlAcesso->getUserID($_SESSION);
$nmUsuario  = $CtrlAcesso->getUserLogin($_SESSION);

$placas         = '';
$sqlCondutor    = '';
$sqlCkbJornada  = '';

$arrayEventos   = array();

/*
    Jornada - Descricao Evento          - Monitoramento
    1       - Chave ligada              - 127
    2       - Chave desligada           - 126
    817     - Manobra Finalizada        - 999
    802     - Jornada Iniciada          - 1014
    803     - Jornada Finalizada        - 1013
    804     - Refeicao Iniciada         - 1012
    805     - Refeicao Finalizada       - 1011
    806     - Descanso Iniciado         - 1010
    807     - Descanso Finalizado       - 1009
    808     - Espera Iniciada           - 1008
    809     - Espera Finalizada         - 1007
    816     - Manobra Iniciada          - 1000
    5015    - Identificacao de condutor - 1006
    6000    - veiculo parado            -
    6001    - veiculo em movimento      -
*/

if($_REQUEST['idCondutor'] != null && $_REQUEST['placa'] == null) {
    $sqlPlacasPorCondutor = "
    SELECT 
          condutor_historico.placa,
          condutor.nome AS nome_condutor
    FROM  monitoramento.condutor_historico,
          monitoramento.condutor
    WHERE condutor.id_condutor = condutor_historico.condutor
          AND condutor = ".$_REQUEST['idCondutor']."
          AND (data_ini BETWEEN to_date('".$_REQUEST['dtIni']."', 'DDMMYYYY') AND to_date('".$_REQUEST['dtFim']."', 'DDMMYYYY')+1
          OR data_ini < to_date('".$_REQUEST['dtIni']."', 'DDMMYYYY') AND data_fim IS NULL
          OR data_fim > to_date('".$_REQUEST['dtIni']."', 'DDMMYYYY'))               
    GROUP BY placa, condutor.nome";

    $respostaPlcCond = oci_parse($conexao, $sqlPlacasPorCondutor);

    if(!oci_execute($respostaPlcCond)) {
//        echo ' Status SELECT PLACAS POR CONDUTOR: ERRO ->'.$sqlPlacasPorCondutor;exit();
    } else {
//        echo ' Status SELECT PLACAS POR CONDUTOR: OK ->'.$sqlPlacasPorCondutor;exit();
    }

    while (($row = oci_fetch_assoc($respostaPlcCond)) != false) {
        if($placas == '') {
            $placas = $row['PLACA'];
        } else {
            $placas = $placas.','.$row['PLACA'];
        }
    }
    oci_free_statement($respostaPlcCond);
    $sqlAuxCond .= "AND tbl.condutor = ".$_REQUEST['idCondutor']." ";
} else {
    $placas = $_REQUEST['placa'];
    if($_REQUEST['idCondutor'] != null) {
        $sqlAuxCond .= "AND tbl.condutor = ".$_REQUEST['idCondutor']." ";
    }
}

if($_REQUEST['ckbJornada'] == 'true') {
    $sqlCkbJornada = ", 1006, 126, 127";
}

$sqlEventos = "
     SELECT condtor.nome condutor,
            tbl.condutor id_condutor,
            tbl.id_evento,
            tbl.placa,
            to_char(tbl.data_hora, 'DD/MM/YYYY HH24:MI') AS data_hora,
            CASE WHEN tbl.id_evento_descricao = 1012 THEN 'Refei&ccedil;&atilde;o Iniciada'
                 WHEN tbl.id_evento_descricao = 1011 THEN 'Refei&ccedil;&atilde;o Finalizada'
            ELSE
                tbl.descricao_evento
            END AS descricao,
            e.nome as nome_empresa
       FROM table(monitoramento.BEG_FNC_EVENTOS(UPPER('".$placas."'),
            P_ID_EVENTO_DESCRICAO=>'',
            P_DT_INICIAL=>to_date('".$_REQUEST['dtIni']." 00:00', 'DDMMYYYY HH24:MI'),
            P_DT_FINAL=>to_date('".$_REQUEST['dtFim']." 23:59', 'DDMMYYYY HH24:MI')+1/24,
            P_CLIENTE_VISUALIZA=>'T',
            P_CALLCENTER_VISUALIZA=>'T',
            P_GERENCIADORA_VISUALIZA=>'T',
            P_EXCECAO=>'')
            ) tbl, monitoramento.condutor condtor, monitoramento.empresa e 
      WHERE id_evento_descricao IN (1014, 1013, 1012, 1011, 1010, 1009, 1008, 1007, 1000, 999".$sqlCkbJornada.")
        AND condtor.id_condutor = tbl.condutor
        AND condtor.empresa = e.id_empresa
        --AND condtor.empresa = ".$_REQUEST['idEmpresa']."
            ".$sqlAuxCond."
   GROUP BY condtor.nome, tbl.condutor, tbl.id_evento, tbl.placa, tbl.data_hora,
                CASE WHEN tbl.id_evento_descricao = 1012 THEN 'Refei&ccedil;&atilde;o Iniciada'
                     WHEN tbl.id_evento_descricao = 1011 THEN 'Refei&ccedil;&atilde;o Finalizada'
                ELSE tbl.descricao_evento END, e.nome 
   ORDER BY tbl.condutor, tbl.data_hora";

$respostaEventos = oci_parse($conexao, $sqlEventos);

if(!oci_execute($respostaEventos)) {
//    echo ' Status SELECT EVENTO: ERRO ->'.$sqlEventos; exit();
} else {
//    echo ' Status SELECT EVENTO: OK ->'.$sqlEventos; exit();
}

while (($row = oci_fetch_assoc($respostaEventos)) != false) {
    $arrayEventos[] = array(
        "idEvento"      => $row['ID_EVENTO'],
        "descEvento"    => $row['DESCRICAO'],
        "dtHrEvento"    => $row['DATA_HORA'],
        "plcEvento"     => $row['PLACA'],
        "nmCondutor"    => $row['CONDUTOR'],
        "nmEmpresa"     => $row['NOME_EMPRESA']
    );
}

oci_free_statement($respostaEventos);
oci_close($conexaoOra);

$array['eventos']   = $arrayEventos;
$json               = $array;
$callback           = $_REQUEST['jsonp'];

//start output
if ($callback) {
    header('Content-Type: text/javascript');
    echo $callback . '(' . json_encode($json) . ');';
} else {
    header('Content-Type: application/x-json');
    echo json_encode($json);
}