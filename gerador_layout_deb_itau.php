<?php

public function onGeraArquivoItau($param = null)
{
        $vencimento = $param['data'];
        $vendedor   = $param['vendedor'];
        $convenio   = $param['convenio'];
        
        function clearString($string = "")
        {
	        $nova_string = $string;

	        if($string)
	        {
        		$comAcentos = array('à', 'á', 'â', 'ã', 'ä', 'å', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ù', 'ü', 'ú', 'ÿ', 'À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'O', 'Ù', 'Ü', 'Ú', 'Ãª', 'ª', 'º');
        		$semAcentos = array('a', 'a', 'a', 'a', 'a', 'a', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'y', 'A', 'A', 'A', 'A', 'A', 'A', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'E', 'a', 'o');
        		$nova_string = str_replace($comAcentos, $semAcentos, $string);
        		$nova_string = str_replace($comAcentos, $semAcentos, utf8_encode($nova_string));
	        }
	       return $nova_string;
        }
        
        if(isset($convenio))
        {
            TTransaction::open(self::$database);        
            
            $convenio_deb_conta = ConveniosDebitoEmConta::where('cod_convenio', '=', $convenio)->first();
            
            $banco = Bancos::find($convenio_deb_conta->banco_id);
            
            $RegistroA                  = array();
        	$RegistroE                  = array();
        	$RegistroZ                  = array();
        	$cod_banco                  = $banco->codigo_febraban;
        	$convenio                   = $convenio_deb_conta->cod_convenio;
        	$contador_registros         = 2;
        	$nome_empresa               = $convenio_deb_conta->nome_empresa;
        	$numero_sequencial_arquivo  = $convenio_deb_conta->numero_sequencial_arquivo  + 1;
        	
        	switch($cod_banco)
        	{
                case 341:
        	         // Adiciona 1 ao numero sequencial do arquivo e insere o valor em convenios debito em conta
                    $adiciona_numero_sequencial_arquivo = ConveniosDebitoEmConta::find($convenio_deb_conta->id);    
                    if($adiciona_numero_sequencial_arquivo)
                    {
                        $adiciona_numero_sequencial_arquivo->numero_sequencial_arquivo = $numero_sequencial_arquivo;
                        $adiciona_numero_sequencial_arquivo->store();    
                    }
                    
                    $RegistroA ["cod_registro"]                  = "A"; 
                    $RegistroA ["cod_remessa"]                   = 1;  
                    $RegistroA ["cod_convenio"]                  = $convenio;
                    $RegistroA ["nome_destinataria"]             = $nome_empresa; 
                    $RegistroA ["cod_depositaria"]               = $cod_banco; 
                    $RegistroA ["nome_depositaria"]              = $banco->nome_banco;
                    $RegistroA ["data_geracao"]                  = date('Ymd');  
                    $RegistroA ["numero_sequencial_arquivo"]     = $numero_sequencial_arquivo;
                    $RegistroA ["versao_layout"]                 = $convenio_deb_conta->versao_layout; 
                    $RegistroA ["identificacao_servico"]         = $convenio_deb_conta->identificacao_servico; 
                    $RegistroA ["reservado_futuro"]              = " ";
                    
                    $content  = '';
                    
         			$content .= itauDebAuto150LayoutCNAB::RegistroA($RegistroA) . PHP_EOL;
                    
                    $condicao = !empty($vendedor) ? " AND N.vendedor_id = $vendedor" :  " ";
                    
                    $conn = TTransaction::get();
                    $sql = "SELECT 
                                negocio_parcelas.id as parcela,
            					negocio_parcelas.negocio_id,
            					negocio_parcelas.documento, 
            					negocio_parcelas.vencimento,   
            					negocio_parcelas.valor,   
            					negocio_parcelas.data_pagamento,  
            					negocio_parcelas.multa,  
            					negocio_parcelas.juros,
            					negocio_parcelas.total, 
            					negocio_parcelas.pagamento_parcelas,
            					negocio_parcelas.cod_retorno,  
            					negocio_parcelas.cod_retorno1,  
            					negocio_parcelas.cod_retorno2,  
            					negocio_parcelas.cod_retorno3,
            					negocio_parcelas.cod_retorno4,
            					negocio_parcelas.cod_retorno5,  
            					negocio_parcelas.numero_parcela,   
            					negocio_parcelas.numero_sequencial_arquivo_debito,
            					negocio_parcelas.numero_registro_e,  
            					negocio_parcelas.numero_agendamento_cliente,
            					negocio_parcelas.vencimento_original,
            					negocio_parcelas.valor_tarifa,
            					negocio_parcelas.status,
            					C.id,
            					C.endereco,
            					C.numero_endereco,
            					C.complemento_endereco,
            					C.bairro,
            					C.nome,
            					C.sobrenome,
            					C.cpf,
            					C.cep,
            					C.cidade,  
            					T.nome_cidade,
            					U.nome_uf,
            					V.cod_convenio,
            					F.dias_antecedencia_cobranca_debito,
            					D.agencia_bancaria,
            					D.conta_corrente,
            					V.mensagem_cliente,
            					V.codigo_carteira,
            					N.vendedor_id
					        FROM 
					            negocio_parcelas
					        INNER JOIN 
					            negocios as N ON N.id = negocio_id
					        INNER JOIN 
					            clientes as C ON N.cliente_id = C.id
					        INNER JOIN 
					            clientes_dados_debito as D ON N.conta_debito = D.id
					        LEFT JOIN 
					            cidades as T ON T.id = C.cidade
					        LEFT JOIN 
					            ufs as U ON U.id = T.uf_cidade
					        INNER JOIN 
					            forma_pagamento as F ON N.forma_pagamento = F.id
					        INNER JOIN 
					           convenios_debito_em_conta as V ON F.cod_convenio = V.id
					        WHERE 
					           negocio_parcelas.numero_registro_e = 0 
					        AND  negocio_parcelas.vencimento <= '$vencimento' 
							AND  V.cod_convenio = $convenio 
							AND  negocio_parcelas.status = 1 $condicao";
					$result = $conn->query($sql);
                    $busca_parcelas = $result->fetchAll(PDO::FETCH_CLASS, "stdClass");
                
                    $nome_arquivo_debito_conta = "ITAU_".$cod_banco."_".$convenio."_".date('ymd')."_".str_pad($numero_sequencial_arquivo, 5 , '0' , STR_PAD_LEFT).".REM";
                    
                    $data_geracao_atual = date('Y-m-d');
                    
                    $insere_dados_arquivo_debito                    = new ArquivosDebitoConta();
                    $insere_dados_arquivo_debito->nome_arquivo      = $nome_arquivo_debito_conta; 
                    $insere_dados_arquivo_debito->tipo_arquivo      = 'REM';
                    $insere_dados_arquivo_debito->data_criacao      = $data_geracao_atual;
                    $insere_dados_arquivo_debito->convenio          = $convenio;  
                    $insere_dados_arquivo_debito->numero_registros  = $contador_registros;
                    $insere_dados_arquivo_debito->arquivo_optante   = 0;
                    $insere_dados_arquivo_debito->numero_arquivo    = $numero_sequencial_arquivo;
                    $insere_dados_arquivo_debito->store();
                    
                    // Busca o id com base no nome do aquivo, data criação e convenio 
                    $conn2 = TTransaction::get();
                    $result2 = $conn->query("SELECT id FROM arquivos_debito_conta WHERE nome_arquivo = '$nome_arquivo_debito_conta' AND data_criacao = '$data_geracao_atual' AND convenio = $convenio");
                    $busca_id_arquivo_debito = $result2->fetchAll(PDO::FETCH_CLASS, "stdClass");
                    
                    
                    if(!empty($busca_parcelas))
                    {
        			    foreach($busca_parcelas as $busca_parcela)
        			    {
                			$converte_cep   = intval($busca_parcela->cep);
        
                			$reg_vencimento = str_replace('-', '', $busca_parcela->vencimento); 
                			
                			$reg_venci = str_replace('-', '', $vencimento); 
            
                			// Verifica se a data de vencimento é menor que a data passada no parâmetro, se sim, atualiza o vencimento para o parametro passado
                			if($reg_vencimento < $reg_venci) 
                			{
                				$data_vencimento = str_replace('-', '', $vencimento); 
                				
                			} else {
                				
                				$data_vencimento = str_replace('-', '', $busca_parcela->vencimento); 
                			}
                            
                            $conn3 = TTransaction::get();
                            $sql   = "UPDATE `negocio_parcelas` SET `numero_registro_e` = " . $contador_registros . ",`numero_sequencial_arquivo_debito` = " . $numero_sequencial_arquivo . " WHERE `id` = " . $busca_parcela->parcela;
                            $result3 = $conn->query($sql);
                            $atualiza_negocio_parcelas = $result3->fetchAll(PDO::FETCH_CLASS, "stdClass");
            
                			// Soma e formata o valor da parcela
                			$soma_valores = $soma_valores + $busca_parcela->total; 
                			
                			$inteiro      = intval($busca_parcela->total); 
                			
                			$centavos     = substr(number_format($busca_parcela->total, 2, ',', '.'), strpos(number_format($busca_parcela->total, 2, ',', '.'), ',', 0) + 1, strlen(number_format($busca_parcela->total, 2, ',', '.'))); 
                
                			$formata_vencimento = date('dmy', strtotime($busca_parcela->vencimento)); 
            
                			$limpa_campo_conta_corrente = [
                                            				'A', 'a', 'B', 'b', 'C', 'c',
                                            				'D', 'd', 'E', 'e', 'F', 'f',
                                            				'G', 'g', 'H', 'h', 'I', 'i',
                                            				'J', 'j', 'K', 'k', 'L', 'l',
                                            				'M', 'm', 'N', 'n', 'O', 'o',
                                            				'P', 'p', 'Q', 'q', 'R', 'r',
                                            				'S', 's', 'T', 't', 'U', 'u',
                                            				'V', 'v', 'W', 'w', 'X', 'x',
                                            				'Y', 'y', 'Z', 'z'
                			                              ];
                		    $RegistroE[ "cod_registro_e"]                = "E";
                            $RegistroE[ "id_cliente_destinataria"]       = 0 . $busca_parcela->cpf;
                            $RegistroE[ "agencia_debito"]                = $busca_parcela->agencia_bancaria;
                            $RegistroE[ "id_cliente_depositaria"]        = intval(str_replace($limpa_campo_conta_corrente, 0, $busca_parcela->conta_corrente));
                            $RegistroE[ "prazo_validade_contrato"]       = $data_vencimento;
                            $RegistroE[ "valor_debito"]                  = intval($inteiro.$centavos);
                            $RegistroE[ "cod_moeda"]                     = 03;
                            $RegistroE[ "uso_instituicao_destinataria"]  = $busca_parcela->parcela;
                            $RegistroE[ "uso_instituicao_destinataria2"] = "X"; 
                            $RegistroE["tipo_identificacao"]             = 2;
                            $RegistroE["identificacao"]                  = $busca_parcela->cpf;
                            $RegistroE["tipo_operacao"]                  = 3;
                            $RegistroE["utilizacao_cheque_especial"]     = 2;
                            $RegistroE["opcao_debito_parcial"]           = 2;
                            $RegistroE["reservado_futuro_E"]             = " ";
                            $RegistroE["cod_movimento"]                  = 0; 
        	
        			        $contador_registros += + 1;
        			        
        			        $content .= itauDebAuto150LayoutCNAB::RegistroE($RegistroE).PHP_EOL;
        			        
        			        $insere_dados_arquivos_debito_conta_retorno                             = new ArquivosDebitoContaRetornos();
                            $insere_dados_arquivos_debito_conta_retorno->data_ocorrencia            = $data_geracao_atual;
                            $insere_dados_arquivos_debito_conta_retorno->arquivo_debito_conta_id    = $busca_id_arquivo_debito[0]->id;
                            $insere_dados_arquivos_debito_conta_retorno->negocio_parcela_id         = $busca_parcela->parcela;
                            $insere_dados_arquivos_debito_conta_retorno->registro                   = $contador_registros;
                            $insere_dados_arquivos_debito_conta_retorno->agencia                    = $busca_parcela->agencia_bancaria;
                            $insere_dados_arquivos_debito_conta_retorno->conta                      = $busca_parcela->conta_corrente;
                            $insere_dados_arquivos_debito_conta_retorno->cliente_id                 = $busca_parcela->id;
                            $insere_dados_arquivos_debito_conta_retorno->status                     = $busca_parcela->status;
                            $insere_dados_arquivos_debito_conta_retorno->cod_convenio               = $convenio_deb_conta->id;
                            $insere_dados_arquivos_debito_conta_retorno->banco                      = $convenio_deb_conta->banco_id;
                            $insere_dados_arquivos_debito_conta_retorno->store();
        		        }
        		        
        		    // Atualiza meu numero de registros na tabela arquivos debito conta
                    $conn5   = TTransaction::get();
                    $sql     = "UPDATE arquivos_debito_conta SET numero_registros = '$contador_registros' WHERE nome_arquivo = '$nome_arquivo_debito_conta' AND data_criacao = '$data_geracao_atual' AND convenio = '$convenio'";
                    $result5 = $conn->query($sql);
                    $atualiza_arquivos_debito_conta = $result5->fetchAll(PDO::FETCH_CLASS, "stdClass");
                    
                    $inteiro 										= intval($soma_valores);
                    $centavos 										= substr(number_format($soma_valores, 2, ',', '.'), strpos(number_format($soma_valores, 2, ',', '.'),',',0)+1, strlen(number_format($soma_valores, 2, ',', '.')));
                    $RegistroZ["cod_registro_z"]                    = "Z";
                    $RegistroZ["total_registros_arquivo"]           = $contador_registros;
                    $RegistroZ["valor_total_registros_arquivo"]     = $inteiro.$centavos;
                    $RegistroZ["reservado_futuro_Z"]                = " "; 

                    $content .= itauDebAuto150LayoutCNAB::RegistroZ($RegistroZ).PHP_EOL;
                    
                    //Cria o arquivo 
                    $nome_arquivo = "ITAU_".$cod_banco."_".$convenio."_".date('ymd')."_".str_pad($numero_sequencial_arquivo, 5 , '0' , STR_PAD_LEFT).".REM";
                    $fp = fopen($_SERVER['DOCUMENT_ROOT'] . "arquivos_debitos/itau/remessa/$nome_arquivo","wb");
                    fwrite($fp,$content);
                    fclose($fp);
                    
                    // Abre o nosso arquivo em modo de leitura
                    $file = fopen($_SERVER['DOCUMENT_ROOT'] . "arquivos_debitos/itau/remessa/$nome_arquivo", 'r');
                
                    // Lê o conteúdo do arquivo
                    $content = fread($file, filesize($_SERVER['DOCUMENT_ROOT'] . "arquivos_debitos/itau/remessa/$nome_arquivo"));
                    
                    // Substitui os caracteres de nova linha por crlf
                    $content = str_replace("\n", "\r\n", $content);
                    
                    fclose($file);
                    
                    // Abra o arquivo em modo de gravação
                    $file = fopen($_SERVER['DOCUMENT_ROOT'] . "arquivos_debitos/itau/remessa/$nome_arquivo", 'w');
                    
                    // Escreve o conteúdo no arquivo
                    fwrite($file, $content);
                    
                    fclose($file);
                    
                    // Script para download 
                    $script = new TElement('script'); 
                    
                    $script->type = 'text/javascript';

                    $script->add("$(document).ready(function(){
                        window.open('downitau.php?file=$nome_arquivo&area=debito_itau', '_blank');
                    });");

                    parent::add($script);
                    
                    TApplication::loadPage('GeradorArquivoClass', 'onShow');
                
                    TToast::show("success", "Arquivo gerado com sucesso!!!", "topRight", "fas:check-circle");
            
                    TTransaction::close();
                }
            }
        }
    }