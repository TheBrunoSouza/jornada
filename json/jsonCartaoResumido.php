<?php
    require_once('../includes/OracleCieloJornada.class.php');
    require_once('../includes/Controles.class.php');
    require_once('../includes/execute.class.php');

    $OraCielo   = new OracleCielo();
    $conexaoOra = $OraCielo->getCon();
    $execute    = new ExecClass($conexaoOra);
    $CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexaoOra);
    $empresaUsu = $CtrlAcesso->getUserEmpresa($_SESSION);
    $sqlEmpUsu  = (!$empresaUsu)?'':'AND cond.empresa = '.$empresaUsu;
    $sqlDtIni   = $_REQUEST['dataIni'];
    $sqlDtFim   = $_REQUEST['dataFim'];

    $sqlCartaoPonto = "
        SELECT 
            sit.id_situacao, 
            cond.id_condutor, 
            cond.nome AS cond, 
            jor.placa AS plc,
            jor.data_fim-jor.data_ini,
            (EXTRACT(HOUR FROM jor.data_fim-jor.data_ini)*60*60) + (EXTRACT(MINUTE FROM jor.data_fim-jor.data_ini)*60) + EXTRACT(SECOND FROM jor.data_fim-jor.data_ini) 
            AS tempo_sec,
            (EXTRACT(HOUR FROM jor.data_fim-jor.data_ini)*60) + EXTRACT(MINUTE FROM jor.data_fim-jor.data_ini) 
            AS tempo,
            ' ('||trim(to_char(EXTRACT(HOUR FROM jor.data_fim-jor.data_ini), '09'))||':'||TRIM(to_char(EXTRACT(MINUTE FROM jor.data_fim-jor.data_ini), '00'))||')'
            AS tempoHrMin,
            sit.descricao AS desc_situacao,
            to_char(jor.data_ini, 'hh24:mi') AS dt_ini, 
            CASE WHEN trunc(jor.data_ini) < trunc(jor.data_fim)
            --THEN '<b>'||to_char(jornada.data_fim, 'hh24:mi')||'<sup>+'||(trunc(data_fim)-trunc(data_ini))||' dia</sup></b>'
            THEN '<br><b>'||to_char(jor.data_fim, 'DD/MM hh24:mi')||'</b>'
            ELSE to_char(jor.data_fim, 'hh24:mi')
            END  AS dt_fim,
            jor.data_ini, 
            jor.data_fim, 
            to_char(jor.data_ini, 'DD/MM') AS dt,
            to_char(jor.data_ini, 'YYYY-MM-DD') AS dt_diario,	
            CASE WHEN to_char(jor.data_ini, 'D') = '1'
            THEN 'Domingo'
            WHEN to_char(jor.data_ini, 'D') = '2'
            THEN 'Segunda'
            WHEN to_char(jor.data_ini, 'D') = '3'
            THEN 'Ter&ccedil;a'
            WHEN to_char(jor.data_ini, 'D') = '4'
            THEN 'Quarta'
            WHEN to_char(jor.data_ini, 'D') = '5'
            THEN 'Quinta'
            WHEN to_char(jor.data_ini, 'D') = '6'
            THEN 'Sexta'
            WHEN to_char(jor.data_ini, 'D') = '7'
            THEN 'S&aacute;bado'
            END AS dia,
            CASE WHEN jor.editado = 'T'
            THEN 'T'
            ELSE 'F'
            END AS justif,
            CASE WHEN jor.afastamento IS NULL
            THEN 'F'
            ELSE 'T'
            END AS afastamento,
            CASE WHEN jor.afastamento IS NULL
            THEN 0 
            ELSE jor.afastamento
            END AS id_afastamento,
            mot.descricao AS desc_afast,
            mot.id_motivo AS id_motivo_afast,
            to_char(afast.data_ini, 'DD/MM/YYYY') AS data_ini_afast,
            to_char(afast.data_fim, 'DD/MM/YYYY') AS data_fim_afast
        FROM  monitoramento.condutor cond, jornada.situacao sit, jornada.jornada jor
        LEFT JOIN jornada.afastamento afast ON jor.afastamento = afast.id_afastamento 
        LEFT JOIN jornada.afast_motivo mot ON afast.motivo = mot.id_motivo
        WHERE jor.condutor = id_condutor
            AND jor.situacao = id_situacao
            AND jor.situacao IN (4, 5, 6, 7, 8, 9, 10)
            AND TRUNC(jor.data_ini) >= to_date('".$sqlDtIni."', 'DD/MM/YYYY') 
            AND TRUNC(jor.data_ini) <= to_date('".$sqlDtFim."', 'DD/MM/YYYY')
            AND cond.id_condutor = ".$_REQUEST['idCondutor']."
            ".$sqlEmpUsu."
            ".$sqlHoras."
        ORDER BY jor.data_ini, jor.data_fim";
    #echo $sqlCartaoPonto; exit();

    $res = oci_parse($conexaoOra, $sqlCartaoPonto);

    if(!oci_execute($res)){
        echo ' Erro no select resumido: '.$sqlCartaoPonto; exit();
        $array['status']    = 'ERRO';
    }else {
        $array['status']    = 'OK';
        $totalRepouso       = 0;
        $totalJornada       = 0;
        $totalRefeicao      = 0;
        $totalDescanso      = 0;
        $totalEspera        = 0;
        $totalExtra         = 0;
        $totalExtra100      = 0;
        $totalNoturna       = 0;
        $countAfastamento   = 0;
        $auxAfast           = 0;
        $rows               = array();
        $dataAux            = '';
        $dataRow            = '';
        $afastamento        = 'F';
        $flag               = 'F';

        //Percorrendo as situacoes
        while (($row = oci_fetch_assoc($res)) != false) {

            //Controlando a montagem do dia anterior (apos percorrer todas as situacoes do dia, a proxima situacao do laco informa que o dia mudou e assim nesta mesma passagem o dia anterior é montado)
            if ($dataAux <> $row['DT'] and $dataAux <> '') {
                $flag = 'T';
            }

            if($flag == 'T'){
                #O dia que sera montado é um afastamento?
                #Este dia possui um afastamento diferente do afastamento do laço?
                if($afastamento == 'T' and $row['ID_AFASTAMENTO'] <> $auxAfast){
                    $rows[] = array(
                        "idCondutor"    => $row['ID_CONDUTOR'],
                        "nmCondutor"    => htmlentities($row['COND']),
                        "Data"          => $periodoAfast,
                        "afastamento"   => $afastamento,
                        "totalJornada"  => $descAfast,
                        "totalExtra"    => $descAfast,
                        "totalExtra100" => $descAfast,
                        "totalEspera"   => $descAfast,
                        "totalRefeicao" => $descAfast,
                        "totalDescanso" => $descAfast,
                        "totalRepouso"  => $descAfast,
                        "totalNoturna"  => $descAfast
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
                    $afastamento        = 'F';
                    $flag               = 'F';
                    $periodoAfast       = '';
                }else{
                    #Quando for um afastamento, os dias nao sao montados e sim agrupados
                    if($afastamento == 'F'){
                        $rows[] = array(
                            "idCondutor"        => $idCondutor,
                            "nmCondutor"        => $nomeCondutor,
                            "Data"              => $dataRow,
                            "afastamento"       => $afastamento,
                            "totalJornada"      => $totalJornada,
                            "totalExtra"        => $totalExtra,
                            "totalExtra100"     => $totalExtra100,
                            "totalEspera"       => $totalEspera,
                            "totalRefeicao"     => $totalRefeicao,
                            "totalDescanso"     => $totalDescanso,
                            "totalRepouso"      => $totalRepouso,
                            "totalNoturna"      => $totalNoturna
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
                        $afastamento        = 'F';
                        $flag               = 'F';
                        $periodoAfast       = '';
                    }
                }
            }

            #Afastamento
            if($row['AFASTAMENTO'] == 'T' and $afastamento == 'F'){
                $afastamento = 'T';
                $descAfast = $row['DESC_AFAST'];
                if ($row['DATA_INI_AFAST'] <> $row['DATA_FIM_AFAST']) {
                    $periodoAfast = $row['DATA_INI_AFAST'] . ' até ' . $row['DATA_FIM_AFAST'];
                } else {
                    $periodoAfast = $row['DT'] . ' - ' . $row['DIA'];
                }
            }

            #Acumulando totais
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

            #Atualizando variaveis de teste para as variaveis do laco
            $dataAux        = $row['DT'];
            $dataRow        = $row['DT'] .' - '.$row['DIA'];
            $nomeCondutor   = $row['COND'];
            $idCondutor     = $row['ID_CONDUTOR'];
            $auxAfast       = $row['ID_AFASTAMENTO'];
        }

        if($afastamento == 'T'){
            #Monta a linha do último dia
            $rows[] = array(
                "idCondutor"        => $row['ID_CONDUTOR'],
                "nmCondutor"        => htmlentities($row['COND']),
                "afastamento"       => $afastamento,
                "Data"              => ($periodoAfast == '')?$dataRow:$periodoAfast,
                "totalJornada"      => $descAfast,
                "totalExtra"        => $descAfast,
                "totalExtra100"     => $descAfast,
                "totalEspera"       => $descAfast,
                "totalRefeicao"     => $descAfast,
                "totalDescanso"     => $descAfast,
                "totalRepouso"      => $descAfast,
                "totalNoturna"      => $descAfast
            );
        }else {
            #Monta a linha do último dia
            $rows[] = array(
                "idCondutor"        => $idCondutor,
                "nmCondutor"        => $nomeCondutor,
                "afastamento"       => $afastamento,
                "Data"              => ($periodoAfast == '')?$dataRow:$periodoAfast,
                "totalJornada"      => $totalJornada,
                "totalExtra"        => $totalExtra,
                "totalExtra100"     => $totalExtra100,
                "totalEspera"       => $totalEspera,
                "totalRefeicao"     => $totalRefeicao,
                "totalDescanso"     => $totalDescanso,
                "totalRepouso"      => $totalRepouso,
                "totalNoturna"      => $totalNoturna
            );
        }

    }

    oci_free_statement($respostaCP);
    oci_close($conexaoOra);

    $array['cartaoPonto']       = $rows;
    $array['status']            = $status;
    $json                       = $array;
    $callback                   = $_REQUEST['jsonp'];
    #print_r($json);

    //start output
    if ($callback) {
        header('Content-Type: text/javascript');
        echo $callback . '(' . json_encode($json) . ');';
    } else {
        header('Content-Type: application/x-json');
        echo json_encode($json);
    }