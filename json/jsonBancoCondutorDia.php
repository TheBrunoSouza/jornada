<?php
    require_once('../includes/OracleCieloJornada.class.php');

    $oraCielo       = new OracleCielo();
    $conexaoOra     = $oraCielo->getCon();

    $idBancoCondutor= $_REQUEST['idBancoCondutor'];
    $idCondutor     = $_REQUEST['idCondutor'];
    $dataIni        = $_REQUEST['dataIni'];
    $dataFim        = $_REQUEST['dataFim'];
    $idBancoHoras   = $_REQUEST['idBancoHoras'];

    $arrayBCDia     = array();

    $sqlBancoCondutorDia = "
        SELECT  bcd.id_bc_dia,
                to_char(bcd.data, 'DD/MM') AS data,
                bcd.condutor,
                bcd.acumulado_ant,
                bcd.minutos_trab,
                CASE 
                  WHEN to_char(bcd.data, 'D') = '1'
                    THEN 'Domingo'
                  WHEN to_char(bcd.data, 'D') = '2'
                    THEN 'Segunda'
                  WHEN to_char(bcd.data, 'D') = '3'
                    THEN 'Ter&ccedil;a'
                  WHEN to_char(bcd.data, 'D') = '4'
                    THEN 'Quarta'
                  WHEN to_char(bcd.data, 'D') = '5'
                    THEN 'Quinta'
                  WHEN to_char(bcd.data, 'D') = '6'
                    THEN 'Sexta'
                  WHEN to_char(bcd.data, 'D') = '7'
                    THEN 'S&aacute;bado'
                END AS dia,
                bcd.acumulado_total,
                bcd.acumulado_dia,
                bcd.feriado,
                bcd.dia_descanso,
                bcd.id_banco_cond,
                bcd.saldo
        FROM    banco_condutor_dia bcd, banco_condutor bc 
        WHERE 
                bcd.id_banco_cond = bc.id_banco_condutor  
                AND bcd.condutor = '".$idCondutor."'
                AND trunc(bcd.data) >= to_date('".$dataIni."', 'DD/MM/YYYY')
                AND trunc(bcd.data) <= to_date('".$dataFim."', 'DD/MM/YYYY')
                AND bc.id_banco_horas = '".$idBancoHoras."'
        ORDER BY bcd.data DESC";

    $respostaBCDia = oci_parse($conexaoOra, $sqlBancoCondutorDia);

    if(!oci_execute($respostaBCDia)){
        echo ' Erro no select de BANCO_CONDUTOR.';
        echo $sqlBancoCondutorDia;
        exit();
    }else{
        #echo $sqlBancoCondutorDia;
        while (($row = oci_fetch_assoc($respostaBCDia)) != false) {
            $arrayBCDia[] = array(
                "id_bc_dia"         => $row['ID_BC_DIA'],
                "data"              => $row['DATA'].' - '.$row['DIA'],
                "dia"               => $row['DIA'],
                "idCondutor"        => $row['CONDUTOR'],
                "acumuladoAnt"      => $row['ACUMULADO_ANT'],
                "minutosTrab"       => $row['MINUTOS_TRAB'],
                "saldo"             => $row['SALDO'],
                "acumuladoTotal"    => $row['ACUMULADO_TOTAL'],
                "acumuladoDia"      => $row['ACUMULADO_DIA'],
                "feriado"           => $row['FERIADO'],
                "descanso"          => $row['DIA_DESCANSO'],
                "idBancoCondutor"   => $row['ID_BANCO_COND']
            );
        }
    }

    $array['bancoCondutorDia'] = $arrayBCDia;

    oci_free_statement($respostaBCDia);
    oci_close($conexaoOra);

    $json = $array;

    //start output
    header('Content-Type: application/x-json');
    echo json_encode($json);