/*

Bandeco
Versão 3.1 - 23/11/2012
Guilherme de Oliveira Souza
http://sitegui.com.br

*/

var _url = "http://sitegui.com.br/apis/bandeco/", _dados = localStorage.getItem("bandecoDados")
var _data = null, _formOuvinte = null, _chave = ""

// Configurações Ajax
Ajax.funcaoErro = function () {
	Aviso.falhar("Falha na conexão", 3e3)
}
Ajax.retorno = "JSON"
var _canal = new CanalAjax // cardápio (somente 1 na fila)
var _canal2 = new CanalAjax // outros (enfileira todos)
_canal.oncarregar = _canal2.oncarregar = function () {
	if (_canal.carregando || _canal2.carregando)
		get("statusConexao").style.opacity = "1"
	else
		get("statusConexao").style.opacity = "0"
}

// Armazena todos os dados da aplicação
// _dados é um objeto com os índices:
// - versao: identifica a versão do formato para evitar incompatibilidades (number)
// - ra: guarda o ra fornecido (string)
// - cache: guarda o cache, indexando pela data (Data.prototype.getHash())
//          cada elemento é um objeto com três índices: "refeicao", "tempo" e "ra"
//          "refeicao" é uma refeição com os índices "historico" e "rank" adicionados
if (_dados != null)
	_dados = JSON.parse(_dados)
if (_dados == null || !("versao" in _dados) || _dados.versao != 3)
	_dados = {versao: 3, ra: "", cache: {}, avisado: false}

onbeforeunload = function () {
	localStorage.setItem("bandecoDados", JSON.stringify(_dados))
}

// Pede pelo RA da pessoa
// Se forcar for true, força o pedido do RA (mesmo quando já foi informado)
// Retorna false caso o RA não tenha sido fornecido
function pedirRA(forcar) {
	var ra
	if (!_dados.ra || forcar) {
		ra = prompt("Qual seu RA?\n(Você precisa fornecê-lo para poder votar nas refeições e ver resultados personalizados)", _dados.ra)
		if (ra === null)
			return false
		_dados.ra = ra
	}
	return true
}

// Mostra a janela
function mostrarJanela(html) {
	get("conteudoJanela").innerHTML = html
	get("janela").style.display = ""
	get("janela").scrollTop = 0
}

// Inicia
onload = function () {
	// Coloca os listeners nos botões
	get("cog").onclick = Menu.abrir([["Mudar RA", function () {
		if (pedirRA(true))
			mostrar()
	}], ["Configurar avisos", configurarAvisos], ["Gerar URL", gerarURL], ["Limpar dados", function () {
		_dados.versao = 0
		location.reload()
	}]])
	get("help").onclick = Menu.abrir([["Sobre", function () {
		mostrarJanela(get("sobre").innerHTML)
	}], ["Fale Conosco", function () {
		window.open("http://sitegui.com.br/fale_conosco/?assunto=bandeco", "janelaFaleConosco", "width=500,height=500")
	}], ["Sitegui", function () {
		window.open("http://sitegui.com.br")
	}], ["API Bandeco", function () {
		window.open("http://sitegui.com.br/apis/bandeco")
	}]])
	get("data").onclick = Menu.abrir([["Ver semana toda", function () {
		mostrarSemana()
	}], ["Ir para data", function () {
		var data = prompt("Digite a data desejada\n(dd ou dd/mm ou dd/mm/aaaa)", _data)
		if (data != null)
			irParaData(data)
	}], ["Ver ranking", function () {
		mostrarRanking()
	}]])
	
	// Exibe o cardápio
	_data = new Data
	lerHash()
	mostrar()
	
	// Copia o molde do form de ouvinte
	_formOuvinte = get("formOuvinte")
	_formOuvinte.parentNode.removeChild(_formOuvinte)
	_formOuvinte.style.display = ""
	
	// Avisa da novidade
	if (!_dados.avisado) {
		setTimeout(avisarNovidade, 45e3)
	}
}

// Avisa que agora pode receber avisos por e-mail
function avisarNovidade() {
	var html
	if (_dados.ra && !_dados.avisado) {
		if (get("janela").style.display != "none") {
			// A janela está em uso, espera um pouco
			setTimeout(avisarNovidade, 1e3)
			return
		}
		html = "<p>Quer ser avisado por e-mail do cardápio da semana ou quando ele for alterado (pra melhor ou pior)?</p>"
		html += "<p><span class='botao' onclick='configurarAvisos()'>Sim, configurar isso agora!</span><br>"
		html += "<span class='botao' onclick='get(\"janela\").style.display=\"none\"' style='font-size:smaller'>Não, deixa pra depois...</span></p>"
		mostrarJanela(html)
		_dados.avisado = true
	}
}

// Lê o comando inicial pela hash
// Formato de exemplo: #J-16/11/2012;tpjfiOrqzOUgrjbi8B85
// A chave é usada para configurar as opções de aviso
// O valor especial "#;!" indica que se deseja configurar avisos
// O valor especial "#;?" indica que se deseja votar nas refeições da semana
// A data pode estar incompleta da direita para a esquerda
// Retorna se a hash foi lida corretamente
function lerHash() {
	var hash = location.hash.match(/^#(?:([AJ])(?:-(\d{2})(?:\/(\d{2})(?:\/(\d{4}))?)?)?)?(?:;(!|\?|.{20}))?$/i)
	if (hash) {
		if (hash[1]) _data.almoco = hash[1].toUpperCase()=="A"
		if (hash[2]) _data.dia = Number(hash[2])
		if (hash[3]) _data.mes = Number(hash[3])
		if (hash[4]) _data.ano = Number(hash[4])
		_data.normalizar()
		
		// Abre o editor de avisos
		if (hash[5])
			if (hash[5] == "?")
				mostrarSemana(true)
			else if (hash[5] == "!")
				configurarAvisos()
			else {
				_chave = hash[5]
				configurarAvisos()
			}
		
		return true
	}
	return false
}
window.onhashchange = function () {
	if (lerHash())
		mostrar()
}

// Vai para uma data específica (pergunta ao usuário)
// data aceita os formatos dd, dd/mm, dd/mm/aaaa ou r-dd/mm/aaaa
function irParaData(data) {
	var partes = data.match(/^(?:([AJ])-)?(\d{1,2})?(?:\/(\d{1,2})(?:\/(\d{4}|\d{2}))?)?$/i)
	if (partes) {
		_data = new Data
		if (partes[1]) _data.almoco = partes[1].toUpperCase()=="A"
		if (partes[2]) _data.dia = Number(partes[2])
		if (partes[3]) _data.mes = Number(partes[3])
		if (partes[4]) _data.ano = Number(partes[4])
		_data.normalizar()
		mostrar()
	}
}

// Atualiza o cache da semana depois de 45s e a cada 2 horas
setInterval(function () {
	// Atualiza o cache da semana
	atualizarCacheSemana()
	
	// Atualiza a visão atual
	_data = new Data
	mostrar()
}, 2*60*60*1e3)
setTimeout(atualizarCacheSemana, 45*1e3)
function atualizarCacheSemana() {
	// Gera funções para salvar o histórico
	var salvar = function (refeicaoAlvo) {
		return function (info) {
			if (info !== null) {
				refeicaoAlvo.historico = info.historico
				refeicaoAlvo.rank = info.rank
			}
			
		}
	}
	
	_canal2.enviar({url: _url+"semana", dados: {ra: _dados.ra}, funcao: function (refeicoes) {
		var i, data, refeicao, dados
		for (i in refeicoes) {
			refeicao = refeicoes[i]
			data = new Data(refeicao.data)
			refeicao.historico = refeicao.rank = null
			_dados.cache[data.getHash()] = {refeicao: refeicao, tempo: Date.now(), ra: _dados.ra}
			
			// Atualiza o histórico e rank
			dados = {prato: refeicao.prato.id, refeicoes: 5, ra: _dados.ra}
			_canal2.enviar({url: _url+"infoPrato", dados: dados, funcao: salvar(refeicao)})
		}
		// Salva forçosamente os dados
		_canal2.enviar(function () {
			onbeforeunload()
			Aviso.avisar("Dados da semana atualizados", 5e3)
		})
	}, funcaoErro: function () {
		Aviso.falhar("Falha na atualização", 5e3)
	}})
}

// Exibe o cardápio (usa o valor global da _data)
// Se naoNotificar for true, não exibe notificação de quando atualizou (usado após o voto)
function mostrar(naoNotificar) {
	var dados, tempo = 0, diferenca, ra = "", cache = null
	
	// Atualiza a data da interface
	get("data").textContent = (_data.almoco ? "Almoço" : "Janta")+" de "+_data.getDiaSemana()+" ("+_data.getResumido()+")"
	
	// Busca no cache
	if (_data.getHash() in _dados.cache) {
		cache = _dados.cache[_data.getHash()]
		tempo = cache.tempo
		ra = cache.ra
		if (!naoNotificar)
			Aviso.avisar("Atualizado "+tempo2String(tempo), 1e3)
		exibirRefeicao(cache.refeicao, false)
	}
	
	if (Date.now()-tempo > 30*60*1e3 || ra != _dados.ra) {
		// Busca a versão atualizada
		dados = {dia: _data.dia, mes: _data.mes, ano: _data.ano, almoco: _data.almoco, ra: _dados.ra}
		_canal.enviarDireto({url: _url+"cardapio", dados: dados, funcao: function (refeicao) {
			var data
			if (refeicao === null)
				// Sem mais dados
				Aviso.falhar("Nenhuma refeição mais", 3e3)
			else {
				data = new Data(refeicao.data)
				if (data.getHash() != _data.getHash())
					// Não é o que foi pedido, descarta
					refeicao = null
				else
					refeicao.historico = refeicao.rank = null
				
				// Armazena e exibe
				_dados.cache[_data.getHash()] = {refeicao: refeicao, tempo: Date.now(), ra: _dados.ra}
				Aviso.avisar("Atualizado!", 1e3)
			}
			exibirRefeicao(refeicao, true)
		}, funcaoErro: function () {
			Aviso.falhar("Falha na conexão", 3e3)
			if (cache === null)
				exibirRefeicao(null)
		}})
	}
}

// Mostra todos os dados da refeição na interface
// Se recarregarHistorico for true, pega o histórico do servidor e salva no cache
function exibirRefeicao(refeicao, recarregarHistorico) {
	var nota, html, i, el, dados, classe
	if (refeicao === null) {
		get("principal").innerHTML = "<em>Sem nada</em>"
		get("guarnicao").textContent = ""
		get("sobremesaESuco").textContent = ""
		get("nota").textContent = ""
		for (i=0; i<5; i++) {
			el = get("voto"+i)
			el.className = ""
		}
		get("historico").textContent = ""
	} else {
		get("principal").textContent = refeicao.prato.nome.upperCaseFirst()
		get("guarnicao").textContent = refeicao.guarnicao.upperCaseFirst()
		if (refeicao.sobremesa && refeicao.suco)
			get("sobremesaESuco").innerHTML = refeicao.sobremesa.upperCaseFirst()+" e suco de "+refeicao.suco
		else if (refeicao.sobremesa)
			get("sobremesaESuco").innerHTML = refeicao.sobremesa.upperCaseFirst()
		else if (refeicao.suco)
			get("sobremesaESuco").innerHTML = "Suco de "+refeicao.suco
		else
			get("sobremesaESuco").innerHTML = ""
		nota = getNotaMedia(refeicao.prato)
		if (nota === null)
			get("nota").textContent = "Sem notas ainda"
		else {
			html = "Nota: <span title='"+refeicao.prato.numVotos+" votos'>"+nota.toFixed(1)+"</span> "+imgTag(nota)
			if (refeicao.prato.notaPessoal !== null)
				html += " (para você: "+refeicao.prato.notaPessoal.toFixed(1)+")"
			get("nota").innerHTML = html
		}
		
		// Mostra a votação
		podeVotar(refeicao)
		for (i=0; i<5; i++) {
			el = get("voto"+i)
			classe = [refeicao.podeVotar ? "botao" : ""]
			if (refeicao.notaPessoal == i-2)
				classe.push("destaque")
			el.className = classe.join(" ")
		}
		
		// Monta o histórico
		if (recarregarHistorico) {
			get("historico").textContent = ""
			dados = {prato: refeicao.prato.id, refeicoes: 5, ra: _dados.ra}
			_canal.enviarDireto({url: _url+"infoPrato", dados: dados, funcao: function (info) {
				if (info !== null) {
					refeicao.historico = info.historico
					refeicao.rank = info.rank
					montarHistorico(refeicao)
				}
			}})
		} else
			montarHistorico(refeicao)
	}
}

// Monta as informações do histórico (e do rank) do prato associado à refeição
// Os dados devem estar nas propriedades "historico" e "rank" do parâmetro
function montarHistorico(refeicao) {
	var i, html = [], nota, data
	if (refeicao.historico === null || refeicao.rank === null) {
		get("historico").textContent = ""
		return
	}
	
	for (i=0; i<refeicao.historico.length; i++) {
		if (refeicao.historico[i].id == refeicao.id)
			continue;
		nota = refeicao.historico[i].nota
		data = new Data(refeicao.historico[i].data)
		html.push("<span title='"+(nota===null ? "Sem nota" : "Nota: "+nota)+"' class='botao' onclick='irParaData(\""+data.getHash()+"\")'>"+
			data.getResumido()+" "+(data.almoco ? "no almoço" : "na janta")+"</span>")
	}
	get("historico").innerHTML = html.length ? "Histórico: "+html.join(", ") : "Histórico desconhecido"
	
	// Coloca o rank
	if (refeicao.rank.posicao)
		get("nota").innerHTML += " ("+refeicao.rank.posicao+"º dentre "+refeicao.rank.total+")"
}

// Forma uma URL direto para essa página
function gerarURL() {
	var html, url
	url = location.protocol+"//"+location.host+location.pathname+"#"+_data.getHash()
	html = "Essa URL irá trazer direto para o cardápio d"+(_data.almoco ? "o almoço" : "a janta")+" do dia "+_data
	html += ":<br><span id='tempInput'>"+url+"</span>"
	mostrarJanela(html)
	setTimeout(function () {
		var range = document.createRange()
		range.selectNode(get("tempInput"))
		getSelection().removeAllRanges()
		getSelection().addRange(range)
	}, 100)
}

// Atalho para document.getElementById
function get(id) {
	return document.getElementById(id)
}

// Retorna uma nota "média" entre o prato e a família
// Usado principalmente para prever a nota de um prato desconhecido
// Retorna null se não houver como calcular essa nota
function getNotaMedia(prato) {
	var soma = 0, n = 0
	if (prato.nota !== null) {
		soma += 2*prato.nota
		n += 2
	}
	if (prato.familia !== null && prato.familia.nota !== null) {
		soma += prato.familia.nota
		n++
	}
	if (n)
		return soma/n
	return null
}

// Verifica se pode votar numa dada refeição
// Retorna e atualiza o campo "podeVotar" da refeição
function podeVotar(refeicao) {
	var dif = Date.now()-(new Data(refeicao.data).getInicio().getTime())
	return refeicao.podeVotar = dif>0 && dif<7*24*60*60*1e3
}

// Transforma um valor de tempo em uma string facilmente entendível
// Date.now()-60e3 => "há 1 minuto"
function tempo2String(tempo) {
	var s = Math.floor((Date.now()-tempo)/1e3)
	var min = Math.floor(s/60)
	var h = Math.floor(min/60)
	var dia = Math.floor(h/24)
	s %= 60
	min %= 60
	h %= 24
	if (dia > 0)
		return "há "+dia+" dia"+(dia==1 ? "" : "s")
	else if (h > 0)
		return "há "+h+" hora"+(h==1 ? "" : "s")
	else if (min > 0)
		return "há "+min+" minuto"+(min==1 ? "" : "s")
	return "há "+s+" segundo"+(s==1 ? "" : "s")
}

// Deixa a primeira letra em maiúscula
Object.defineProperty(String.prototype, "upperCaseFirst", {value: function () {
	return this.charAt(0).toUpperCase()+this.substr(1)
}})

// Retorna a tag HTML para a imagem do smile que representa a nota dada
function imgTag(nota) {
	nota = Math.round(nota)
	return "<img src='"+nota+".png' title='"+nota+"'>"
}

// Avança e retorna no tempo (usando os botões ou o teclado)
function avancar() {
	_data.avancar()
	mostrar()
}
function voltar() {
	_data.voltar()
	mostrar()
}
onkeydown = function (e) {
	if (e.keyCode == 39)
		avancar()
	else if (e.keyCode == 37)
		voltar()
}

// Mostra o cardápio da semana
// Se votar for true, abre o painel para votar nas refeições da semana
function mostrarSemana(votar) {
	_canal2.enviar({url: _url+"semana", dados: {ra: _dados.ra, semana: _data.getSemana()}, funcao: function (refeicoes) {
		var i, refeicao, data, nota, html = "<p><span class='botao' onclick='votarSemana()'>Vote nas refeições dessa semana</span></p>"
		html += "<table id='tabelaSemana'><tr><td>Data</td><td>Prato</td><td>Nota</td><td>Sobremesa</td></tr>"
		for (i in refeicoes) {
			refeicao = refeicoes[i]
			data = new Data(refeicao.data)
			
			// Coloca vários dados da refeição no html
			html += "<tr data-data='"+data.getHash()+"'>"
			
			html += "<td>"+(refeicoes[i].data.almoco ? "Almoço" : "Janta")+" de "+data.getDiaSemana()+" <span style='font-size:smaller'>("+data.getResumido()+")</span></td>"
			html += "<td>"+refeicoes[i].prato.nome.upperCaseFirst()+"</td>"
			nota = getNotaMedia(refeicao.prato)
			
			if (nota === null)
				html += "<td>-</td>"
			else
				html += "<td title='"+refeicao.prato.numVotos+" votos nesse prato'>"+nota.toFixed(1)+" "+imgTag(nota)+"</td>"
			html += "<td>"+refeicoes[i].sobremesa.upperCaseFirst()+"</td></tr>"
			
			// Salva no cache
			refeicao.historico = refeicao.rank = null
			_dados.cache[data.getHash()] = {refeicao: refeicao, tempo: Date.now(), ra: _dados.ra}
		}
		mostrarJanela("</table>"+html)
		
		if (votar)
			votarSemana()
	}})
}

// Mostra o cardápio da semana passada para a pessoa poder votar
function votarSemana() {
	var tabela, i, linha, html, nota, classe, refeicao
	
	if (!pedirRA())
		return
	
	// Modifica a tabela, criando o formulário
	tabela = get("tabelaSemana")
	for (i=0; i<tabela.rows.length; i++) {
		linha = tabela.rows.item(i)
		linha.cells.item(3).style.display = "none"
		if (!i)
			linha.cells.item(2).textContent = "Voto"
		else {
			if (_dados.cache[linha.dataset.data].ra != _dados.ra) {
				// Não tem as notas corretas carregas, recarrega a semana toda
				mostrarSemana(true)
				return
			}
			refeicao = _dados.cache[linha.dataset.data].refeicao
			html = ""
			if (refeicao.podeVotar)
				for (nota=-2; nota<=2; nota++) {
					classe = nota==refeicao.notaPessoal ? "botao destaque" : "botao"
					html += "<img src='"+nota+".png' title='"+nota+"' id='voto"+(nota+2)+"Linha"+i+"'"
					html += "onclick='votarSemanaClick("+i+", "+nota+")' class='"+classe+"'> "
				}
			else {
				html = "Votação fechada"
				if (refeicao.notaPessoal)
					html += " em <img src='"+refeicao.notaPessoal+".png' title='"+refeicao.notaPessoal+"'>"
			}
			linha.cells.item(2).innerHTML = html
		}
	}
	
	// Modifica o texto
	get("conteudoJanela").firstChild.textContent = "Escolha abaixo os votos nas refeições dessa semana"
}

// Processa o clique num voto da semana
function votarSemanaClick(linha, voto) {
	var data = get("tabelaSemana").rows.item(linha).dataset.data
	var refeicao = _dados.cache[data].refeicao, dados, i
	get("voto"+(voto+2)+"Linha"+linha).blur()
	
	// Atualiza a interface
	if (get("voto"+(voto+2)+"Linha"+linha).className.indexOf("destaque") != -1)
		voto = null
	for (i=-2; i<=2; i++)
		get("voto"+(i+2)+"Linha"+linha).className = i==voto ? "botao destaque" : "botao"
	
	dados = {refeicao: refeicao.id, ra: _dados.ra}
	if (voto != null)
		dados.voto = voto
	
	_canal2.enviar({url: _url+"votar", dados: dados, funcao: function (ok) {
		if (ok) {
			Aviso.avisar("Voto registrado", 1e3)
			refeicao.notaPessoal = voto
			mostrar(true)
		} else
			Aviso.falhar("Voto inválido", 3e3)
	}, metodo: "POST"})
}

// Mostra o rank dos pratos
function mostrarRanking(pagina) {
	pagina = pagina || 0
	_canal2.enviar({url: _url+"ranking", dados: {inicio: pagina*50, quantidade: 50, ra: _dados.ra}, funcao: function (pratos) {
		var i, prato, html = "<br><table><tr><td>Pos</td><td>Prato</td><td>Nota</td></tr>"
		if (pratos.length == 0) {
			Aviso.falhar("Sem mais dados", 3e3)
			return
		}
		for (i in pratos) {
			prato = pratos[i]
			html += "<tr><td>"+(pagina*50+Number(i)+1)+"º</td><td>"+prato.nome.upperCaseFirst()+"</td>"
			html += "<td title='"+pratos[i].numVotos+" votos nesse prato'>"+prato.nota.toFixed(1)+" "+imgTag(prato.nota)+"</td></tr>"
		}
		html += "</table>"
		if (pratos.length == 50)
			html += "<br><span class='botao' onclick='mostrarRanking("+(pagina+1)+")'>Ver mais</span>"
		mostrarJanela(html)
	}})
}

// Abre a janela para configurar o ouvinte do usuário atual
function configurarAvisos() {
	var html = "<p>Agora você pode ser avisado por e-mail quando o cardápio da semana ficar disponível ou quando ele for alterado</p>"
	
	_dados.avisado = true
	if (_chave) {
		// Abre o formulário de edição
		_canal2.enviar({url: _url+"getOuvinte", dados: {chave: _chave}, funcao: function (ouvinte) {
			var form, html
			if (ouvinte === null) {
				Aviso.falhar("Chave incorreta", 3e3)
				return
			}
			html = "<p>Editando os dados do RA "+ouvinte.ra+"</p>"
			mostrarJanela(html)
			form = _formOuvinte.cloneNode(true)
			get("conteudoJanela").appendChild(form)
			get("avisoRA").value = ouvinte.ra
			get("avisoNome").value = ouvinte.nome
			get("avisoEmail").value = ouvinte.email
			get("checkSemana").checked = ouvinte.avisos & 1
			get("checkRuim").checked = ouvinte.avisos & 2
			get("checkBom").checked = ouvinte.avisos & 4
		}})
		return
	}
	
	// Pede o RA
	if (!_dados.ra) {
		html += "<p>Para continuar, <span class='botao' onclick='pedirRA();configurarAvisos()'>informe seu RA</span></p>"
		mostrarJanela(html)
		return
	}
	
	if (!_chave) {
		// Verifica se precisa de chave
		_canal2.enviar({url: _url+"pedirChave", dados: {ra: _dados.ra}, funcao: function (precisa) {
			var form
			if (precisa)
				mostrarJanela("<p>Um link foi enviado para seu e-mail, clique nele para continuar</p>")
			else {
				html += "<p>Informe seus dados:</p>"
				mostrarJanela(html)
				form = _formOuvinte.cloneNode(true)
				get("conteudoJanela").appendChild(form)
				get("avisoRA").value = _dados.ra
				get("btRemoverOuvinte").style.display = "none"
				get("avisoNome").focus()
			}
		}, metodo: "POST"})
	}
}

// Remove o ouvinte cadastrado
function removerOuvinte() {
	if (confirm("Deseja realmente descadastrar seu e-mail?")) {
		_canal2.enviar({url: _url+"removerOuvinte", dados: {chave: _chave}, funcao: function (ok) {
			if (ok) {
				Aviso.avisar("Cadastro excluído", 3e3)
				_chave = ""
			} else
				Aviso.falhar("Não foi possível excluir cadastro", 3e3)
		}, metodo: "POST"})
		
		// Apaga o forms e a janela
		get("janela").style.display = "none"
		get("conteudoJanela").removeChild(get("formOuvinte"))
	}
}

// Salva as definições do ouvinte
function salvarOuvinte() {
	var ra, nome, email, avisos = 0, dados
	
	// Pega os valores
	ra = get("avisoRA").value
	nome = get("avisoNome").value
	email = get("avisoEmail").value
	if (get("checkSemana").checked) avisos += 1
	if (get("checkRuim").checked) avisos += 2
	if (get("checkBom").checked) avisos += 4
	get("janela").style.display = "none"
	
	// Salva
	dados = {ra: ra, nome: nome, email: email, avisos: avisos, chave: _chave}
	_canal2.enviar({url: _url+"setOuvinte", dados: dados, funcao: function (sucesso) {
		if (sucesso)
			Aviso.avisar("Dados salvos com sucesso", 1e3)
		else
			Aviso.falhar("Dados inconsistentes", 3e3)
	}, metodo: "POST"})
	
	// Exclui o form da página
	get("conteudoJanela").removeChild(get("formOuvinte"))
}

// Vota na refeição
// Retorna se o clique foi válido
function votar(num) {
	var refeicao = _dados.cache[_data.getHash()].refeicao, dados
	get("voto"+(num+2)).blur()
	
	if (!podeVotar(refeicao) || !pedirRA())
		return false
	
	dados = {refeicao: refeicao.id, ra: _dados.ra}
	if (refeicao.notaPessoal != num)
		dados.voto = num
	else
		num = null
	
	_canal2.enviar({url: _url+"votar", dados: dados, funcao: function (ok) {
		if (ok) {
			Aviso.avisar("Voto registrado", 1e3)
			refeicao.notaPessoal = num
			mostrar(true)
		} else
			Aviso.falhar("Voto inválido", 3e3)
	}, metodo: "POST"})
	
	return true
}
