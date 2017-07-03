<?
require_once('../includes/OracleCieloJornada.class.php');

$OraCielo   = new OracleCielo();
$conexaoOra = $OraCielo->getCon();

$exp        = explode('-', $_REQUEST['data']);
$dtEdt      = $exp[2].$exp[1].$exp[0];
$arrayDesl  = "";

$sqlDeslocamento = "
    SELECT 
        id_relatorio,
        to_char(data_ini, 'hh24:mi') AS hora_ini,
        to_char(data_fim, 'hh24:mi') AS hora_fim,
        getintervaltochar(data_ini, data_fim) as tempo,
        placa,
        CASE  WHEN situacao = 1
                THEN 'Desligado'
              WHEN situacao = 2
                THEN 'Movimento'
              WHEN situacao = 3
                THEN 'Parado'
              END AS situacao,
        empresa,
        condutor
    FROM monitoramento.relatorio_viagem
    WHERE condutor = ".$_REQUEST['idCondutor']."
        AND trunc(data_ini) = to_date('".$dtEdt."', 'DDMMYYYY')
    ORDER BY id_relatorio";

$respostaDesl = oci_parse($conexaoOra, $sqlDeslocamento);

if(!oci_execute($respostaDesl)){
    #echo ' Status SELECT: ERRO ->'.$sqlDeslocamento;exit();
}else{
    #echo ' Status SELECT: OK ->'.$sqlDeslocamento;exit();
    while (($row = oci_fetch_assoc($respostaDesl)) != false) {

        $arrayDesl[] = array(
            "idRelatorio"   => $row['ID_RELATORIO'],
            "hora_ini"      => $row['HORA_INI'],
            "hora_fim"      => $row['HORA_FIM'],
            "tempo"         => $row['TEMPO'],
            "placa"         => $row['PLACA'],
            "situacao"      => $row['SITUACAO'],
            "empresa"       => $row['EMPRESA'],
            "condutor"      => $row['CONDUTOR']
        );
    }
}
#print_r($arrayDesl);exit();

oci_free_statement($respostaDesl);
oci_close($conexaoOra);

$array['Relatorio'] = $arrayDesl;
$json               = $array;

//start output
//if ($callback) {
//    header('Content-Type: text/javascript');
//    echo $callback . '(' . json_encode($json) . ');';
//} else {
    header('Content-Type: application/x-json');
    echo json_encode($json);
//}