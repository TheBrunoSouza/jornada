<?php
    require_once('../includes/OracleCieloJornada.class.php');
    require_once('../includes/Controles.class.php');

    $OraCielo   = new OracleCielo();
    $conexaoOra = $OraCielo->getCon();
    $CtrlAcesso = new Controles($_SERVER['REMOTE_ADDR'], $conexao);
    $empresaUsu = $CtrlAcesso->getUserEmpresa($_SESSION);
    #print_r($_REQUEST);exit();
    
    $sqlLogJornada = "SELECT DISTINCT(jg.descricao),
                             to_char(jg.data_alt, 'DD/MM/YYYY') AS data_alt,
                             to_char(jg.data_db, 'DD/MM/YYYY') AS data_db,
                             to_char(TRUNC(jg.data_ini), 'DD/MM/YYYY') AS data_ini,
                             cond.nome,
                             jg.usuario as id_usuario,
                             us.nome as nome_usuario
                        FROM usuario us, jornada_log jg
                   LEFT JOIN monitoramento.condutor cond ON cond.id_condutor = jg.CONDUTOR
                       WHERE trunc(data_ini) >= to_date('".$_REQUEST['dtIni']."', 'DD/MM/YYYY')
                         AND trunc(data_fim) <= to_date('".$_REQUEST['dtFim']."', 'DD/MM/YYYY')
                         AND jg.condutor = '".$_REQUEST['idCondutor']."'
                         AND jg.descricao IS NOT NULL 
                         AND jg.data_db IS NOT NULL
                         AND jg.usuario = us.id_usuario
                    GROUP BY jg.descricao, 
                             jg.data_alt, 
                             jg.data_db, 
                             jg.data_ini, 
                             cond.nome,
                             jg.usuario,
                             us.nome
                    ORDER BY data_ini";   

    $respostaLogJornada = oci_parse($conexaoOra, $sqlLogJornada);

    if(!oci_execute($respostaLogJornada)){
        echo ' Status SELECT JORNADA_LOG: ERRO ->'.$sqlLogJornada;
        //exit();
    } else {
        //echo ' Status SELECT JORNADA_LOG: OK->'.$sqlLogJornada;
        //exit();

        while (($row = oci_fetch_assoc($respostaLogJornada)) != false) {
            //echo $row['DESCRICAO'];
            $arrayLogJornada[] = array(           
                "dataIni" => $row['DATA_INI'] ? $row['DATA_INI'] : '-',
                "dataAlteracao" => $row['DATA_ALT'],
                "idUsuario" => $row['ID_USUARIO'],
                "nomeUsuario" => $row['NOME_USUARIO'],
                "descricao" => utf8_encode($row['DESCRICAO'])
            );
        }
        $array['logJornada'] = $arrayLogJornada;
    }
    oci_free_statement($respostaLogJornada);

    oci_close($conexaoOra);

    //$json = $array;

    //header('Content-Type: application/x-json');
    echo json_encode($array);