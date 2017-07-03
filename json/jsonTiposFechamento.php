<?php
    require_once('../includes/OracleCieloJornada.class.php');

    $oraCielo               = new OracleCielo();
    $conexaoOra             = $oraCielo->getCon();
    $arrayTiposFechamento   = array();
    $sqlTiposFechamento     = "SELECT * FROM banco_tipo_fechamento";
    $respostaTipos          = oci_parse($conexaoOra, $sqlTiposFechamento);

    if(!oci_execute($respostaTipos)){
        echo ' Erro no select de afastamentos.';
        echo $sqlPeriodo;
        exit();
    }else{
        while (($row = oci_fetch_assoc($respostaTipos)) != false) {
            $arrayTiposFechamento[] = array(
                "idTipoFechamento"     => $row['ID_TIPO_FECHAMENTO'],
                "descTipoFechamento"   => $row['DESCRICAO']
            );
        }
    }

    $array['tiposFechamento'] = $arrayTiposFechamento;

    oci_free_statement($respostaTipos);
    oci_close($conexaoOra);

    $json = $array;

    //start output
    header('Content-Type: application/x-json');
    echo json_encode($json);