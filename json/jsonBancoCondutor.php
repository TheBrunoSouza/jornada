<?php
    require_once('../includes/OracleCieloJornada.class.php');

    $oraCielo   = new OracleCielo();
    $conexaoOra = $oraCielo->getCon();

    $sqlIdEmpresa       = '';
    $sqlIdCondutor      = '';
    $idCondutor         = $_REQUEST['idCondutor'];
    $idEmpresa          = $_REQUEST['idEmpresa'];
    $arrayBancoCondutor = array();

    if($idEmpresa <> ''){
        $sqlIdEmpresa = "AND bh.empresa = '".$idEmpresa."'";
    }

    if($idCondutor <> ''){
        $sqlIdCondutor = " AND bc.condutor = '".$idCondutor."'";
    }

    $sqlBancoCondutor = "
        SELECT 
            bc.id_banco_condutor,
            mc.nome as nome_condutor,
            bc.condutor,
            bc.saldo_ini,
            bc.acumulado,
            bc.saldo_atual,
            bc.id_banco_horas,
            bc.data_ini,
            bc.data_fim,
            bh.min_semana as min_sem_bh,
            bh.min_sab as min_sab_bh,
            bh.vencimento as vencimento_bh,
            pbh.descricao as desc_periodo_bh,
            bc.min_total
        FROM banco_condutor bc, banco_horas bh, banco_horas_periodo pbh, monitoramento.condutor mc 
        WHERE 
            bc.condutor = mc.id_condutor
            AND bc.id_banco_horas = bh.id_banco_horas
            AND bh.periodo = pbh.id_periodo
            AND bc.ativo =  'T'
            $sqlIdEmpresa
            $sqlIdCondutor
         ORDER BY mc.nome   
    ";

    $respostaBancoCondutor = oci_parse($conexaoOra, $sqlBancoCondutor);

    if(!oci_execute($respostaBancoCondutor)){
        echo ' Erro no select de BANCO_CONDUTOR.';
        echo $sqlBancoCondutor;
        exit();
    }else{
        #echo $sqlBancoCondutor;
        while (($row = oci_fetch_assoc($respostaBancoCondutor)) != false) {
            $arrayBancoCondutor[] = array(
                "idBancoCondutor"   => $row['ID_BANCO_CONDUTOR'],
                "nomeCondutor"      => $row['NOME_CONDUTOR'],
                "idCondutor"        => $row['CONDUTOR'],
                "saldoIni"          => $row['SALDO_INI'],
                "acumulado"         => $row['ACUMULADO'],
                "saldoAtual"        => $row['SALDO_ATUAL'],
                "idBancoHoras"      => $row['ID_BANCO_HORAS'],
                "dataIni"           => $row['DATA_INI'],
                "dataFim"           => $row['DATA_FIM'],
                "minSemBH"          => $row['MIN_SEM_BH'],
                "minSabBH"          => $row['MIN_SAB_BH'],
                "minTotalBC"        => $row['MIN_TOTAL'],
                "vencimentoBH"      => $row['VENCIMENTO_BH'],
                "descPeriodo"       => $row['DESC_PERIODO_BH']
            );
        }
    }

    $array['bancoCondutor'] = $arrayBancoCondutor;

    oci_free_statement($respostaBancoCondutor);
    oci_close($conexaoOra);

    $json = $array;

    //start output
    header('Content-Type: application/x-json');
    echo json_encode($json);