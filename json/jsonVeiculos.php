<?
    session_start();

    require_once('../includes/OracleCieloJornada.class.php');

    $OraCielo = new OracleCielo();
    $conexaoOra = $OraCielo->getCon();

    //$data_request = json_decode(file_get_contents('php://input'));
    $json_request = file_get_contents('php://input');
    $json = json_decode($json_request);
    $arrayPlc = array();

    $idEmpresa = $_REQUEST['idEmpresa'];

    print_r($json);

    $sqlPlaca = "SELECT DISTINCT(veic.id_veiculo),
                        UPPER(TRIM(veic.placa)) placa
                   FROM monitoramento.VEICULO veic,
                        monitoramento.VEICULO_EMPRESA veie
                  WHERE TRIM(veic.placa) = TRIM(veie.placa)
                    AND UPPER(TRIM(veic.ativo)) = 'SIM'
                    AND veie.empresa = '".$idEmpresa."'
               ORDER BY placa ASC";
    
    #echo '<pre>'.$sqlPlaca.'</pre>'; exit();

    $respostaPlaca = oci_parse($conexaoOra, $sqlPlaca);

    if(!oci_execute($respostaPlaca)){
    //    echo ' Status SELECT: ERRO ->'.$sqlPlaca;exit();
    }else{
    //    echo ' Status SELECT: OK ->'.$sqlPlaca;exit();
    }

    while (($row = oci_fetch_assoc($respostaPlaca)) != false) {
        $arrayPlc[] = array(
            "placa" => $row['PLACA']
        );
    }

    $array['veiculos']  = $arrayPlc;
    $json = $array;
    //start output
    /*
    if ($callback) {
        header('Content-Type: text/javascript');
        echo $callback . '(' . json_encode($json) . ');';
    } else {*/
        header('Content-Type: application/x-json');
        echo json_encode($json);
    //}