<?php
    require_once('../includes/OracleCieloJornada.class.php');

    $oraCielo   = new OracleCielo();
    $conexaoOra = $oraCielo->getCon();
    $arrayAfast = array();

    $sqlAfastamentos = "
        SELECT 
          afast.id_afastamento,
          afast.motivo,
          to_char(afast.data_ini, 'DD/MM/YYYY') AS data_ini,
          to_char(afast.data_fim, 'DD/MM/YYYY') AS data_fim,
          afast.obs,
          afast.usuario,
          to_char(afast.data_cad, 'DD/MM/YYYY') AS data_cad,
          afast.condutor,
          m.descricao as desc_motivo
        FROM afastamento afast, afast_motivo m
        WHERE afast.motivo = m.id_motivo 
          AND condutor = ".$_REQUEST['idCondutor'];

    $respostaAfastamentos = oci_parse($conexaoOra, $sqlAfastamentos);

    if(!oci_execute($respostaAfastamentos)){
        echo ' Erro no select de afastamentos.';
        echo $sqlAfastamentos;
        exit();
    }else{
        while (($row = oci_fetch_assoc($respostaAfastamentos)) != false) {
            $arrayAfast[] = array(
                "idAfastamento"     => $row['ID_AFASTAMENTO'],
                "idMotivo"          => $row['MOTIVO'],
                "descMotivo"        => $row['DESC_MOTIVO'],
                "dataIni"           => $row['DATA_INI'],
                "dataFim"           => $row['DATA_FIM'],
                "obs"               => $row['OBS'],
                "usuario"           => $row['USUARIO'],
                "dataCad"           => $row['DATA_CAD'],
                "condutor"          => $row['CONDUTOR']
            );
        }
    }

    $array['afastamentos'] = $arrayAfast;

    oci_free_statement($respostaAfastamentos);
    oci_close($conexaoOra);

    $json = $array;

    //start output
    header('Content-Type: application/x-json');
    echo json_encode($json);