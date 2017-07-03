<?php
    require_once('../includes/OracleCieloJornada.class.php');

    $oraCielo   = new OracleCielo();
    $conexaoOra = $oraCielo->getCon();
$arrayPeriodo = array();

    $sqlPeriodo = "SELECT * FROM banco_horas_periodo ORDER BY TOTAL_SEMANAS ASC";

    $respostaPeriodo = oci_parse($conexaoOra, $sqlPeriodo);

    if(!oci_execute($respostaPeriodo)){
        echo ' Erro no select de afastamentos.';
        echo $sqlPeriodo;
        exit();
    }else{
        while (($row = oci_fetch_assoc($respostaPeriodo)) != false) {
            $arrayPeriodo[] = array(
                "idPeriodo"         => $row['ID_PERIODO'],
                "descPeriodo"       => $row['DESCRICAO'],
                "totalSemanas"      => $row['TOTAL_SEMANAS']
            );
        }
    }

    $array['periodos'] = $arrayPeriodo;

    oci_free_statement($respostaPeriodo);
    oci_close($conexaoOra);

    $json = $array;

    //start output
    header('Content-Type: application/x-json');
    echo json_encode($json);