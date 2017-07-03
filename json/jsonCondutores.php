<?php
    error_reporting(0);

    require_once('../includes/OracleCieloJornada.class.php');

    $OraCielo       = new OracleCielo();
    $conexao        = $OraCielo->getCon();
    $json_request   = file_get_contents('php://input');
    $json           = json_decode($json_request);
    $sqlAux         = '';

    if($_REQUEST['idEmpresa']) {
        $sqlAux .= " AND emp.id_empresa = " . $_REQUEST['idEmpresa'] . "";
    }else {
        exit();
    }

    if($_REQUEST['nmCondutor']) {
        $sqlAux .= " AND UPPER(cond.nome) LIKE UPPER('" . $_REQUEST['nmCondutor'] . "%') ";
    }

    $sqlCond = "
        SELECT 
            cond.id_condutor, 
            cond.nome AS cond_nome, 
            cond.cpf, 
            cond.identidade,
            cond.celular, 
            cond.matricula,
            emp.nome AS emp_nome,
            to_char(cond.data_nascimento, 'DD/MM/YYYY') AS data_nascimento, 
            veic.placa,
            cond.profissao,
            cond.empresa AS id_empresa
            --sit.descricao
        FROM monitoramento.empresa emp,
            empresa_jornada,
            monitoramento.condutor cond
        LEFT JOIN monitoramento.veiculo veic ON veic.condutor = cond.id_condutor
        LEFT JOIN jornada jor ON jor.condutor = cond.id_condutor
        --LEFT JOIN situacao sit ON jor.situacao = sit.id_situacao
        WHERE cond.empresa = emp.id_empresa
            AND cond.ativo = 'T'			
            AND emp.id_empresa = empresa_jornada.empresa
            ".$sqlAux."
        GROUP BY 
            cond.id_condutor, 
            cond.nome, 
            cond.cpf, 
            cond.identidade,
            cond.celular, 
            cond.matricula,
            emp.nome,
            cond.data_nascimento, 
            veic.placa,
            cond.profissao,
            cond.empresa
        ORDER BY cond.nome ";

    #echo "<pre>".$sqlCond."</pre>";
    #exit;

    $resCond = OCIParse($conexao, $sqlCond);
    OCIExecute($resCond);

    $arrayCond = array();

    while(OCIFetchInto($resCond, $ddCond, OCI_ASSOC)){
        $arrayCond[] = array(
            "idCondutor"            => $ddCond['ID_CONDUTOR'],
            "nmCondutor"            => utf8_encode($ddCond['COND_NOME']),
            "profissaoCondutor"     => utf8_encode($ddCond['PROFISSAO']),
            "cpfCondutor"           => $ddCond['CPF'],
            "rgCondutor"            => $ddCond['IDENTIDADE'],
            "celularCondutor"       => $ddCond['CELULAR'],
            "matriculaCondutor"     => $ddCond['MATRICULA'],
            "dtNascCondutor"        => $ddCond['DATA_NASCIMENTO'],
            "empCondutor"           => $ddCond['EMP_NOME'],
            "plcCondutor"           => $ddCond['PLACA'],
            "idEmpresa"             => $ddCond['ID_EMPRESA']
        );
    }
    //print_r($arrayCond);
    //exit;

    $array['condutores'] = $arrayCond;
    $json = $array;

    $callback = $_REQUEST['jsonp'];

    //start output
    if ($callback) {
    	header('Content-Type: text/javascript');
    	echo $callback . '(' . json_encode($json) . ');';
    } else {
    	header('Content-Type: application/x-json');
    	echo json_encode($json);
    }

