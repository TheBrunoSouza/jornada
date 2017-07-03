<?
require_once('../includes/Controles.class.php');
$CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexaoOra);

require_once('../includes/OracleCieloJornada.class.php');
$OraCielo=new OracleCielo();
$conexao=$OraCielo->getCon();

$idEmpresa = $CtrlAcesso->getUserEmpresa($_SESSION);

$sqlSituacao = "SELECT count(nome) qtd, dsc
                FROM (SELECT condutor.nome, MAX(jornada.data_ini) dt_ini
                            ,MAX(data_fim) keep (dense_rank last order by jornada.data_ini) dt_fim
                            ,MAX(situacao.descricao) keep (dense_rank last order by jornada.data_ini) dsc
                             --, FIRST_VALUE(jornada.data_fim)  OVER (ORDER BY jornada.data_ini DESC)
                      FROM jornada, monitoramento.condutor, situacao
                      WHERE condutor.empresa = ".$idEmpresa."
                      AND condutor.ativo = 'T'
                      AND jornada.situacao = situacao.id_situacao
                      AND condutor.id_condutor = jornada.condutor
                      --AND TRUNC(jornada.data_ini) = TRUNC(SYSDATE)
                      GROUP BY nome
                      ORDER BY jornada.data_ini DESC)
                GROUP BY dsc";
//echo "<pre>".$sqlSituacao."</pre>";
$resSituacao = OCIParse($conexao, $sqlSituacao);
OCIExecute($resSituacao);
$arrsit = array();
while(OCIFetchInto($resSituacao, $rowSit, OCI_ASSOC)){
    $arrsit[] = array(
        "name"     => '('.$rowSit['QTD'].') '.$rowSit['DSC'],
        "data1"    => $rowSit['QTD']
    );
}
$array = array();
$array['dados'] = $arrsit;
$json = $array;

header('Content-Type: application/x-json');
//header('Content-Type: text/javascript');
echo json_encode($json);
?>