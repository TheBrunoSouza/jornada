<?
require_once('../includes/OracleCieloJornada.class.php');

$oraCielo               = new OracleCielo();
$conexaoOra             = $oraCielo->getCon();
$sqlAfastMotivos        = "SELECT * FROM afast_motivo";
$respostaAfastMotivos   = oci_parse($conexaoOra, $sqlAfastMotivos);

if(!oci_execute($respostaAfastMotivos)){
    echo ' Erro no select de motivos.';
    echo $sqlAfastMotivos;
    exit();
}else{
    while (($row = oci_fetch_assoc($respostaAfastMotivos)) != false) {
        $arrayMotivos[] = array(
            "idMotivo"  => $row['ID_MOTIVO'],
            "descricao" => $row['DESCRICAO'],
        );
    }
}

$array['motivos'] = $arrayMotivos;

oci_free_statement($respostaAfastMotivos);
oci_close($conexaoOra);

$json = $array;

//start output
header('Content-Type: application/x-json');
echo json_encode($json);