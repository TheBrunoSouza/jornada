<?php
    require_once('../includes/OracleCieloJornada.class.php');

    $oraCielo           = new OracleCielo();
    $conexaoOra         = $oraCielo->getCon();
    $idEmpresa          = $_REQUEST['idEmpresa'];
    $arrayBancoHoras    = array();

    $sqlBancoHoras = "
            SELECT 
                bh.id_banco_horas,
                to_char(bh.data_ini, 'DD/MM/YYYY') as data_ini,
                bh.min_semana,
                bh.user_id_cad,
                to_char(bh.data_cad, 'DD/MM/YYYY') as data_cad,
                bh.min_sab,
                bh.user_name_cad,
                bh.vencimento,
                bh.periodo as id_periodo,
                pbh.descricao as desc_periodo,
                bh.empresa as id_empresa
            FROM banco_horas bh, banco_horas_periodo pbh
            WHERE bh.periodo = pbh.id_periodo
                AND empresa = '".$idEmpresa."'
                AND ativo = 'T'
            ORDER BY bh.data_ini
    ";

    $respostaBancoHoras = oci_parse($conexaoOra, $sqlBancoHoras);

    if(!oci_execute($respostaBancoHoras)){
        echo ' Erro no select de BANCO_HORAS.';
        echo $sqlBancoHoras;
        exit();
    }else{
        #echo $sqlBancoHoras;
        while (($row = oci_fetch_assoc($respostaBancoHoras)) != false) {
            $arrayBancoHoras[] = array(
                "idBancoHoras" => $row['ID_BANCO_HORAS'],
                "dataIni"      => $row['DATA_INI'],
                "minSemana"    => $row['MIN_SEMANA'],
                "userIdCad"    => $row['USER_ID_CAD'],
                "dataCad"      => $row['DATA_CAD'],
                "minSabado"    => $row['MIN_SAB'],
                "userNameCad"  => $row['USER_NAME_CAD'],
                "vencimento"   => $row['VENCIMENTO'],
                "idPeriodo"    => $row['ID_PERIODO'],
                "descPeriodo"  => $row['DESC_PERIODO'],
                "idEmpresa"    => $row['ID_EMPRESA']
            );
        }
    }

    $array['bancoHoras'] = $arrayBancoHoras;

    oci_free_statement($respostaBancoHoras);
    oci_close($conexaoOra);

    $json = $array;

    //start output
    header('Content-Type: application/x-json');
    echo json_encode($json);