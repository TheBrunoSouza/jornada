<?php
    error_reporting(0);
    //ini_set('display_errors', 1);
    //error_reporting(E_ALL);
    session_start();

    require_once('../includes/OracleCieloJornada.class.php');
    $OraCielo = new OracleCielo();
    $conexao = $OraCielo->getCon();

    //$data_request = json_decode(file_get_contents('php://input'));
    $json_request = file_get_contents('php://input');
    $json = json_decode($json_request);

    //print_r($json); // ExtJS JSON post request

    if($json->idEmpresa) {
	$sqlDel = "DELETE FROM empresa WHERE id_empresa = '".$json->idEmpresa."'";
	//echo $sqlDel;
	$resDel = OCIParse($conexao, $sqlDel);
	//OCIExecut($resDel);
    }
    
    $sqlAux = "";

    if($_REQUEST['nmEmpresa'])
        $sqlAux .= " AND UPPER(empresa.nome) LIKE '".strtoupper(trim($_REQUEST['nmEmpresa']))."%' ";

    $sqlCli = "SELECT id_empresa,
                      empresa.nome,
                      empresa.responsavel,
                      empresa.telefone,
                      empresa.email, 
                      ej.teclado_alfa, 
                      to_char(ej.data_ingresso, 'DD/MM/YY HH24:MI') as dt,
                      ej.usuario_cad
                 FROM monitoramento.empresa,
                      empresa_jornada ej
                WHERE empresa.id_empresa = ej.empresa
                  AND ej.ativa = 'T'
                      ".$sqlAux."
             ORDER BY empresa.nome";
    
    #echo "<pre>".$sqlCli."</pre>"; exit();
    
    $resCli = OCIParse($conexao, $sqlCli);
    OCIExecute($resCli);
    $arrayCli = array();
    while(OCIFetchInto($resCli, $ddCli, OCI_ASSOC)) {
	$arrayCli[] = array(
            "idEmpresa"=>$ddCli['ID_EMPRESA'],
            "nmEmpresa"=>utf8_encode($ddCli['NOME']),
            "respEmpresa"=>utf8_encode($ddCli['RESPONSAVEL']),
            "telEmpresa"=>$ddCli['TELEFONE'],
            "emailEmpresa"=>$ddCli['EMAIL'],
            "usuEmpresa"=>$ddCli['RESPONSAVEL'],
            "dataAtivacao"=>$ddCli['DT'],
            "teclado"=>$ddCli['TECLADO_ALFA'],
            "usuarioCad"=>$ddCli['USUARIO_CAD']);
    }
    //print_r($arrayCli);
    $array['empresas'] = $arrayCli;
    $json = $array;

    $callback = $_REQUEST['jsonp'];

    oci_free_statement($resCli);
    oci_close($conexao);

    //start output
    if ($callback) {
        header('Content-Type: text/javascript');
        echo $callback . '(' . json_encode($json) . ');';
    } else {
        header('Content-Type: application/x-json');
        echo json_encode($json);
    }
?>
