<?php
require_once('../includes/OracleCieloJornada.class.php');

$oraCielo                   = new OracleCielo();
$conexaoOra                 = $oraCielo->getCon();

$idEmpresa                  = $_REQUEST['idEmpresa'];
$idCondutor                 = $_REQUEST['idCondutor'];
$dataIniBF                  = $_REQUEST['dataIniBF'];
$dataFimBF                  = $_REQUEST['dataFimBF'];
$dataIniBC                  = $_REQUEST['dataIniBC'];
$dataFimBC                  = $_REQUEST['dataFimBC'];
$radioPendente              = $_REQUEST['radioPendente'];
$radioFechado               = $_REQUEST['radioFechado'];
$radioTodos                 = $_REQUEST['radioTodos'];
$tipoFechamento             = $_REQUEST['tipoFechamento'];


$arrayBCFechamento          = array();

$sqlCondutor                = "";
$sqlTipoFechamento          = "";
$sqlDataIniBF               = "";
$sqlDataFimBF               = "";
$sqlDataIniBC               = "";
$sqlDataFimBC               = "";
//Garante que ao clicar no button 'Fechamentos', a consulta retorne penas registros pendentes
$sqlFechamento              = " AND tipo_fechamento IS NULL";

//Filtro para o status do fechamento (pendente / fechado / todos)
if($radioPendente == 'true'){
    $sqlFechamento = " AND tipo_fechamento IS NULL";
}
if($radioFechado == 'true'){
    $sqlFechamento = " AND tipo_fechamento IS NOT NULL";
}
if($radioTodos == 'true'){
    $sqlFechamento = "";
}

//Filtro de condutor
if($idCondutor != ''){
    $sqlCondutor = " AND bf.condutor = ".$idCondutor;
}

//Filtro do tipo de fechamento, ajustando o sqlFechamento
if($tipoFechamento != ''){
    $sqlTipoFechamento = " AND bf.tipo_fechamento = ".$tipoFechamento;
    $sqlFechamento = " AND tipo_fechamento IS NOT NULL";
}

//Data ini e fim do banco fechamento se refere a data de fechamento feita pelo usuario
if($dataIniBF != ''){
    $sqlDataIniBF = " AND trunc(bf.dt_fechamento) >= to_date('".$dataIniBF."', 'DD/MM/YYYY')";
}
if($dataFimBF != ''){
    $sqlDataFimBF = " AND trunc(bf.dt_fechamento) <= to_date('".$dataFimBF."', 'DD/MM/YYYY')";
}

//Data ini e fim  do banco condutor se refere ao período do banco.
if($dataIniBC != ''){
    $sqlDataIniBC = " AND trunc(bf.data_ini) = to_date('".$dataIniBC."', 'DD/MM/YYYY')";
}else{
    $sqlDataIniBC = " AND trunc(bf.data_ini) >= add_months(SYSDATE, -3)";
}
if($dataFimBC != ''){
    $sqlDataFimBC = " AND trunc(bf.data_fim) = to_date('".$dataFimBC."', 'DD/MM/YYYY')";
}else{
    $sqlDataFimBC = " AND trunc(bf.data_fim) <= SYSDATE";
}

$sqlBFechamento = "
        SELECT  
            bf.id_banco_fechamento,
            bf.condutor as id_condutor,
            mc.nome as nome_condutor,
            bf.saldo,
            to_char(bf.data_ini, 'DD/MM/YYYY') AS data_ini,
            to_char(bf.data_fim, 'DD/MM/YYYY') AS data_fim,
            bf.tipo_fechamento,
            tf.descricao as desc_tp_fechamento,
            bf.id_banco_horas,
            bf.total_trabalhado,
            bh.min_semana,
            bh.min_sab,
            bh.vencimento,
            bhp.descricao as desc_periodo,
            bh.empresa as id_empresa,
            to_char(bf.dt_fechamento, 'DD/MM/YYYY') AS data_fechamento,
            bf.obs_fechamento,
            bf.user_fechamento as user_id_fechamento,
            u.nome as user_name_fechamento,
            bf.media_trab_dia,
            bf.dias_valor_dobrado,
            bf.total_previsto,
            bf.media_trab_sem,
            bf.id_banco_condutor
        FROM banco_fechamento bf  
        LEFT JOIN monitoramento.condutor mc ON bf.condutor = mc.id_condutor
        LEFT JOIN banco_horas bh ON bf.id_banco_horas = bh.id_banco_horas
        LEFT JOIN banco_tipo_fechamento tf ON bf.tipo_fechamento = tf.id_tipo_fechamento
        LEFT JOIN banco_horas_periodo bhp ON bh.periodo =  bhp.id_periodo
        LEFT JOIN usuario u ON bf.user_fechamento = u.id_usuario
        WHERE
            bh.empresa = '".$idEmpresa."'
            ".$sqlCondutor."
            ".$sqlDataIniBF."
            ".$sqlDataFimBF."
            ".$sqlDataIniBC."
            ".$sqlDataFimBC."
            ".$sqlTipoFechamento."
            ".$sqlFechamento."
        ORDER BY bf.data_ini, mc.nome";

$respostaBFechamento = oci_parse($conexaoOra, $sqlBFechamento);

if(!oci_execute($respostaBFechamento)){
    echo ' Erro no select de BANCO_CONDUTOR.';
    echo $sqlBFechamento;
    exit();
}else{
    #echo $sqlBFechamento;
    while (($row = oci_fetch_assoc($respostaBFechamento)) != false) {
        $arrayBCFechamento[] = array(
            "idBancoFechamento"     => $row['ID_BANCO_FECHAMENTO'],
            "idCondutor"            => $row['ID_CONDUTOR'],
            "nomeCondutor"          => $row['NOME_CONDUTOR'],
            "saldoBF"               => $row['SALDO'],
            "dataIniBF"             => $row['DATA_INI'],
            "dataFimBF"             => $row['DATA_FIM'],
            "tipoFechamento"        => $row['TIPO_FECHAMENTO'],
            "descTipoFechamentoBF"  => $row['DESC_TP_FECHAMENTO'],
            "idBancoHoras"          => $row['ID_BANCO_HORAS'],
            "totalTrabalhadoBF"     => $row['TOTAL_TRABALHADO'],
            "minSemBH"              => $row['MIN_SEMANA'],
            "minSabBH"              => $row['MIN_SAB'],
            "totalPrevisto"         => $row['TOTAL_PREVISTO'],
            "vencimentoBH"          => $row['VENCIMENTO'],
            "descPeriodoBH"         => $row['DESC_PERIODO'],
            "idEmpresa"             => $row['ID_EMPRESA'],
            "userNameFechamento"    => $row['USER_NAME_FECHAMENTO'],
            "obsFechamento"         => $row['OBS_FECHAMENTO'],
            "dataFechamento"        => $row['DATA_FECHAMENTO'],
            "mediaTrabDia"          => $row['MEDIA_TRAB_DIA'],
            "mediaTrabSem"          => $row['MEDIA_TRAB_SEM'],
            "diasValorDobrado"      => $row['DIAS_VALOR_DOBRADO'],
            "idBancoCondutor"       => $row['ID_BANCO_CONDUTOR']
        );
    }
    oci_free_statement($respostaBFechamento);
}

$array['bancoFechamento'] = $arrayBCFechamento;
oci_close($conexaoOra);

$json = $array;

//start output
header('Content-Type: application/x-json');
echo json_encode($json);