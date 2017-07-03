<?php
    require_once('../includes/OracleCieloJornada.class.php');

    $oraCielo               = new OracleCielo();
    $conexaoOra             = $oraCielo->getCon();
    $arrayTpFechamento      = array();
    $sqlTpFechamento        = "SELECT * FROM banco_tipo_fechamento";
    $respostaTpFechamento   = oci_parse($conexaoOra, $sqlTpFechamento);

    if(!oci_execute($respostaTpFechamento)){
        echo ' Erro no select de tipos de afastamento.';
        echo $sqlTpFechamento;
        exit();
    }else{
        while (($row = oci_fetch_assoc($respostaTpFechamento)) != false) {
            $arrayTpFechamento[] = array(
                "idTpFechamento"     => $row['ID_TIPO_FECHAMENTO'],
                "descTpFechamento"   => $row['DESCRICAO']
            );
        }
    }

    $array['tipoFechamentos'] = $arrayTpFechamento;

    oci_free_statement($respostaTpFechamento);
    oci_close($conexaoOra);

    $json = $array;

    //start output
    header('Content-Type: application/x-json');
    echo json_encode($json);