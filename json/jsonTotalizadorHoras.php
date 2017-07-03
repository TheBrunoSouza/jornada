<?php
    require_once('../includes/OracleCieloJornada.class.php');
    require_once('../includes/Controles.class.php');
    require_once('../includes/execute.class.php');

    $OraCielo       = new OracleCielo();
    $execute        = new ExecClass($conexaoOra);
    $conexaoOra     = $OraCielo->getCon();
    $CtrlAcesso     = new Controles($_SERVER['REMOTE_ADDR'], $conexaoOra);
    $idCondutor     = $_REQUEST['idCondutor'];
    $dataIni        = $_REQUEST['dtIni'];
    $dataFim        = $_REQUEST['dtFim'];
    $empresaUsu     = $CtrlAcesso->getUserEmpresa($_SESSION);
    $idEmpresa      = (!$empresaUsu or $empresaUsu == null)?$_REQUEST['idEmpresa']:$empresaUsu;
    $sqlAuxCondutor = "AND cond.id_condutor = '".$idCondutor."'";

    if($idCondutor == null){$sqlAuxCondutor = "";}

    $sqlTotalizador = "
        SELECT 
            sit.id_situacao,  
            cond.id_condutor, 
            cond.nome AS nome_condutor,
            (EXTRACT(HOUR FROM jor.data_fim-jor.data_ini)*60) + EXTRACT(MINUTE FROM jor.data_fim-jor.data_ini) 
            AS tempo,
            to_char(jor.data_ini, 'hh24:mi') AS dt_ini,
            to_char(jor.data_ini, 'DD/MM') AS dt,
            CASE WHEN trunc(jor.data_ini) < trunc(jor.data_fim)
            --THEN '<b>'||to_char(jornada.data_fim, 'hh24:mi')||'<sup>+'||(trunc(data_fim)-trunc(data_ini))||' dia</sup></b>'
            THEN '<br><b>'||to_char(jor.data_fim, 'DD/MM hh24:mi')||'</b>'
            ELSE to_char(jor.data_fim, 'hh24:mi')
            END  AS dt_fim,
            CASE WHEN jor.afastamento IS NULL
            THEN 'F'
            ELSE 'T'
            END AS afastamento
        FROM monitoramento.condutor cond, jornada.jornada jor, jornada.situacao sit
        WHERE jor.condutor = id_condutor
            AND jor.situacao = id_situacao  
            AND jor.situacao IN (4, 5, 6, 7, 8, 9, 10)
            AND TRUNC(jor.data_ini) >= to_date('".$dataIni."', 'DDMMYYYY') 
            AND TRUNC(jor.data_ini) <= to_date('".$dataFim."', 'DDMMYYYY')
            AND cond.empresa = '".$idEmpresa."'
            ".$sqlAuxCondutor."
        ORDER BY cond.id_condutor, jor.data_ini, jor.data_fim";
    #echo $sqlTotalizador; exit();

    $res = oci_parse($conexaoOra, $sqlTotalizador);

    if(!oci_execute($res)){
        echo ' Erro no select totalizador: '.$sqlTotalizador; exit();
        $array['status'] = 'ERRO';
    }else {
        $array['status'] = 'OK';

        $totalRepouso       = 0;
        $totalJornada       = 0;
        $totalRefeicao      = 0;
        $totalDescanso      = 0;
        $totalEspera        = 0;
        $totalExtra         = 0;
        $totalExtra100      = 0;
        $idCondutorAux      = 0;
        $totalNoturna       = 0;
        $countAfastamento   = 0;

        $rows       = array();
        $flag       = 'F';
        $dataAux    = '';

        while (($row = oci_fetch_assoc($res)) != false) {

            //Se mudou o condutor ou é o primeiro registro
            if ($idCondutorAux <> $row['ID_CONDUTOR'] and $idCondutorAux <> 0) {
                $flag = 'T';
            }

            if($flag == 'T'){
                $rows[] = array(
                    "idCondutor"        => $idCondutor,
                    "nomeCondutor"      => $nomeCondutor,
                    "totalRepouso"      => $totalRepouso,
                    "totalJornada"      => $totalJornada,
                    "totalRefeicao"     => $totalRefeicao,
                    "totalDescanso"     => $totalDescanso,
                    "totalEspera"       => $totalEspera,
                    "totalExtra"        => $totalExtra,
                    "totalExtra100"     => $totalExtra100,
                    "totalNoturna"      => $totalNoturna,
                    "totalAfastamento"  => $countAfastamento*1440
                );

                $totalRepouso       = 0;
                $totalJornada       = 0;
                $totalRefeicao      = 0;
                $totalDescanso      = 0;
                $totalEspera        = 0;
                $totalExtra         = 0;
                $totalExtra100      = 0;
                $idCondutorAux      = 0;
                $totalNoturna       = 0;
                $countAfastamento   = 0;

                $flag       = 'F';
                $dataAux    = '';
            }

            if($row['AFASTAMENTO'] == 'F'){
                switch ($row['ID_SITUACAO']) {
                    case 4:
                        $totalRepouso = $totalRepouso + $row['TEMPO'];
                        break;
                    case 5:
                        $totalJornada = $totalJornada + $row['TEMPO'];
                        break;
                    case 6:
                        $totalRefeicao = $totalRefeicao + $row['TEMPO'];
                        break;
                    case 7:
                        $totalDescanso = $totalDescanso + $row['TEMPO'];
                        break;
                    case 8:
                        $totalEspera = $totalEspera + $row['TEMPO'];
                        break;
                    case 9:
                        $totalExtra = $totalExtra + $row['TEMPO'];
                        break;
                    case 10:
                        $totalExtra100 = $totalExtra100 + $row['TEMPO'];
                        break;
                }

                //Controle de jornada noturna
                if($row['ID_SITUACAO'] == 5 or $row['ID_SITUACAO'] == 9 or $row['ID_SITUACAO'] == 10){
                    if ($row['DT_INI'] <> null and $row['DT_FIM'] <> null) {
                        $totalNoturna += $execute->totalHorasNoturnas($row['DT_INI'], $row['DT_FIM']);
                    }
                }
            }else{
                //Afastamentos
                if($dataAux <> $row['DT']){
                    $countAfastamento++;
                }
            }

            $dataAux        = $row['DT'];
            $idCondutorAux  = $row['ID_CONDUTOR'];
            $idCondutor     = $row['ID_CONDUTOR'];
            $nomeCondutor   = $row['NOME_CONDUTOR'];
        }

        #Monta a linha do último condutor
        $rows[] = array(
            "idCondutor"        => $idCondutor,
            "nomeCondutor"      => $nomeCondutor,
            "totalRepouso"      => $totalRepouso,
            "totalJornada"      => $totalJornada,
            "totalRefeicao"     => $totalRefeicao,
            "totalDescanso"     => $totalDescanso,
            "totalEspera"       => $totalEspera,
            "totalExtra"        => $totalExtra,
            "totalExtra100"     => $totalExtra100,
            "totalNoturna"      => $totalNoturna,
            "totalAfastamento"  => $countAfastamento*1440
        );
    }

    oci_free_statement($res);
    oci_close($conexaoOra);

    $array['cartaoPonto'] = $rows;
    $json = $array;

    #print_r($json);

    header('Content-Type: application/x-json');
    echo json_encode($json);