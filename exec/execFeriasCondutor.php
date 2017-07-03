<?php
    error_reporting(0);
    session_start();

    require_once('../includes/OracleCieloJornada.class.php');

    $OraCielo   = new OracleCielo();
    $conexaoOra = $OraCielo->getCon();

    $sqlSalvaFerias = "
        INSERT INTO ferias(
            id_ferias,
            data_ini,
            data_fim,
            condutor,
            usuario,
            data_cadastro,
            desc_ferias
        )
        VALUES (
            nextval(''), 
            ".$_REQUEST['dataIni'].",
            ".$_REQUEST['dataFim'].",
            ".$_REQUEST['idCondutor'].",
            ".$_REQUEST['idUsuario'].",
            '".$_REQUEST['descFerias']."',
            dataatual
        )
    ";

    $respostaSalvaFerias = oci_parse($conexaoOra, $sqlSalvaFerias);

    if(!oci_execute($respostaSalvaFerias)){
//        $status = 'ERRO';
//        $msg = 'Favor informar o departamento de TI';
        echo ' Status DELETE: ERRO ->'.$sqlDeleteUsuario; exit();
    } else {
//        $status = 'OK';
//        $msg = '';
        echo ' Status DELETE: OK ->'.$sqlDeleteUsuario; exit();
    }
    oci_free_statement($respostaSalvaFerias);
    oci_close($conexaoOra);


    $retorno['status']  = $status;
    $retorno['msg'] = $msg;
    $json = $retorno;

    //start output
    header('Content-Type: application/x-json');
    echo json_encode($json);