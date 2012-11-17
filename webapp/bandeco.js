/*

Bandeco
Versão 3.0 - 16/11/2012
Guilherme de Oliveira Souza
http://sitegui.com.br

*/

var _url = "../api/", _dados = localStorage.getItem("bandecoDados")
var _data = null

// Canal usado para as requisições GET
var _canal = new CanalAjax

// Armazena todos os dados da aplicação
// _dados é um objeto com os índices:
// - versao: identifica a versão do formato para evitar incompatibilidades (number)
// - ra: guarda o ra fornecido (string)
// - cache: guarda o cache, indexando pela data (Data.prototype.getHash())
//          cada elemento é um objeto com três índices: "refeicao", "tempo" e "ra"
//          "refeicao" é uma refeição com os índices "historico" e "rank" adicionados
// - votos: armazena os votos ainda não enviados para o servidor (TODO: decidir formato)
if (_dados != null)
	_dados = JSON.parse(_dados)
if (_dados == null || !("versao" in _dados) || _dados.versao != 3.1)
	_dados = {versao: 3.1, ra: "", cache: {}, votos: []}

onbeforeunload = function () {
	localStorage.setItem("bandecoDados", JSON.stringify(_dados))
}

// Pede pelo RA da pessoa
// Se forcar for true, força o pedido do RA (mesmo quando já foi informado)
// Retorna false caso o RA não tenha sido fornecido
function pedirRA(forcar) {
	var ra
	if (!_dados.ra || forcar) {
		ra = prompt("Qual seu RA?\n(Você precisa fornece-lo para poder votar nas refeições)", _dados.ra)
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
}

// TODO: implementar
var mostrarRank, mostrarSemana

// Inicia
// TODO: Avisar que agora tem notificações por e-mail!
onload = function () {
	// Coloca os listeners nos botões
	get("cog").onclick = Menu.abrir([["Ver ranking", mostrarRank], ["Mudar RA", function () {
		if (pedirRA(true))
			mostrar()
	}], ["Gerar URL", gerarURL], ["Limpar dados", function () {
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
	get("data").onclick = Menu.abrir([["Ver semana", mostrarSemana], ["Ir para data", function () {
		var data = prompt("Digite a data desejada\n(dd ou dd/mm ou dd/mm/aaaa)", _data)
		if (data)
			irParaData(data)
	}]])
	
	// Exibe o cardápio
	_data = new Data
	lerHash()
	mostrar()
}

// Lê o comando inicial pela hash
// Formato de exemplo: #J-16/11/2012;tpjfiOrqzOUgrjbi8B85
// A chave é usada para configurar as opções de aviso
// A data pode estar incompleta da direita para a esquerda
// Retorna se a hash foi lida corretamente
function lerHash() {
	var hash = location.hash.match(/^#(?:([AJ])(?:-(\d{2})(?:\/(\d{2})(?:\/(\d{4}))?)?)?)?(?:;(.{20}))?$/i)
	if (hash) {
		if (hash[1]) _data.almoco = hash[1].toUpperCase()=="A"
		if (hash[2]) _data.dia = Number(hash[2])
		if (hash[3]) _data.mes = Number(hash[3])
		if (hash[4]) _data.ano = Number(hash[4])
		_data.normalizar()
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
	var partes = data.match(/^(?:([AJ])-)?(\d{1,2})(?:\/(\d{1,2})(?:\/(\d{4}|\d{2}))?)?$/i)
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

// Atualiza o cache da semana a cada 2 horas
setInterval(function () {
	
}, 2*60*60*1e3)

// Exibe o cardápio (usa o valor global da _data)
function mostrar() {
	var dados, tempo = 0, diferenca, ra = "", cache
	
	// Atualiza a data da interface
	get("data").textContent = (_data.almoco ? "Almoço" : "Janta")+" de "+_data.getDiaSemana()+" ("+_data+")"
	
	// Busca no cache
	if (_data.getHash() in _dados.cache) {
		cache = _dados.cache[_data.getHash()]
		tempo = cache.tempo
		ra = cache.ra
		Aviso.avisar("Atualizado "+tempo2String(tempo), 1e3)
		exibirRefeicao(cache.refeicao, false)
	}
	
	if (Date.now()-tempo > 30*60*1e3 || ra != _dados.ra) {
		// Busca a versão atualizada
		dados = {dia: _data.dia, mes: _data.mes, ano: _data.ano, almoco: _data.almoco, ra: _dados.ra}
		_canal.enviarDireto({url: _url+"cardapio", dados: dados, retorno: "JSON", funcao: function (refeicao) {
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
				exibirRefeicao(refeicao, true)
				Aviso.avisar("Atualizado!", 1e3)
			}
		}, funcaoErro: function () {
			Aviso.falhar("Falha na conexão", 3e3)
		}})
	}
}

// Mostra todos os dados da refeição na interface
// Se recarregarHistorico for true, pega o histórico do servidor e salva no cache
function exibirRefeicao(refeicao, recarregarHistorico) {
	var nota, html, i, el, dados
	if (refeicao === null) {
		get("principal").innerHTML = "<em>Sem nada</em>"
		get("guarnicao").textContent = ""
		get("sobremesaESuco").textContent = ""
		get("nota").textContent = ""
		for (i=0; i<5; i++) {
			el = get("voto"+i)
			el.classList.remove("botao")
			el.classList.remove("destaque")
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
			el.classList[refeicao.podeVotar ? "add" : "remove"]("botao")
			if (refeicao.notaPessoal == i-2)
				el.classList.add("destaque")
			else
				el.classList.remove("destaque")
		}
		
		// Monta o histórico
		if (recarregarHistorico) {
			get("historico").textContent = ""
			dados = {prato: refeicao.prato.id, refeicoes: 5, ra: _dados.ra}
			_canal.enviarDireto({url: _url+"infoPrato", dados: dados, retorno: "JSON", funcao: function (info) {
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
	for (i=0; i<refeicao.historico.length; i++) {
		if (refeicao.historico[i].id == refeicao.id)
			continue;
		nota = refeicao.historico[i].nota
		data = new Data(refeicao.historico[i].data)
		html.push("<span title='Em "+data.ano+", "+(nota===null ? "sem nota" : "nota: "+nota)+"' class='botao' onclick='irParaData(\""+data.getHash()+"\")'>"+
			data.dia+"/"+data.mes+" "+(data.almoco ? "no almoço" : "na janta")+"</span>")
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

/*
setInterval(function () {
	if (navigator.onLine) {
		if (ajax)
			ajax.abortar()
		ajax = Ajax({url: url+"cardapio?ra="+dados.ra,
		retorno: "json",
		funcao: montar,
		funcaoErro: function () {
			document.body.style.cursor = ""
		}})
		dados.delta = 0
		document.body.style.cursor = "progress"
	}
}, 3600e3)

// Mostra o cardápio da semana
function mostrarSemana() {
	document.body.style.cursor = "progress"
	if (ajax)
		ajax.abortar()
	ajax = Ajax({url: url+"semana",
	dados: {ra: dados.ra},
	retorno: "json",
	funcao: function (refeicoes) {
		var i, data, data2, html = "<br><table><tr><td>Data</td><td>Prato</td><td>Nota</td><td>Sobremesa</td></tr>"
		document.body.style.cursor = ""
		for (i=0; i<refeicoes.length; i++) {
			data = new Date(refeicoes[i].data.ano, refeicoes[i].data.mes-1, refeicoes[i].data.dia, 15, 0, 0, 0)
			data = dias[data.getDay()]
			data2 = refeicoes[i].data.dia.getCom2Digitos()+"/"+refeicoes[i].data.mes.getCom2Digitos()
			html += "<tr><td>"+(refeicoes[i].data.almoco ? "Almoço" : "Janta")+" de "+data+" <span style='font-size:smaller'>("+data2+")</span></td>"
			html += "<td>"+refeicoes[i].prato.nome.upperCaseFirst()+"</td>"
			if (refeicoes[i].prato.numVotos)
				html += "<td title='"+refeicoes[i].prato.numVotos+" votos'>"+Math.round(refeicoes[i].prato.nota*100)/100+" "+imgTag(refeicoes[i].prato.nota)+
				"</td>"
			else
				html += "<td>-</td>"
			html += "<td>"+refeicoes[i].sobremesa.upperCaseFirst()+"</td></tr>"
		}
		mostrarJanela(html)
	},
	funcaoErro: function () {
		document.body.style.cursor = ""
		alert("Erro na conexão")
	}})
}

// Mostra o rank dos pratos
function mostrarRank() {
	document.body.style.cursor = "progress"
	if (ajax)
		ajax.abortar()
	ajax = Ajax({url: url+"ranking",
	dados: {ra: dados.ra, quantidade: 50},
	retorno: "json",
	funcao: function (pratos) {
		var i, html = "<br><table><tr><td>Pos</td><td>Prato</td><td>Nota</td></tr>"
		document.body.style.cursor = ""
		for (i=0; i<pratos.length; i++) {
			html += "<tr><td>"+(i+1)+"º</td><td>"+pratos[i].nome.upperCaseFirst()+"</td><td title='"+pratos[i].numVotos+" votos'>"+Math.round(pratos[i].nota*100)/100+
			"</td></tr>"
		}
		mostrarJanela(html)
	},
	funcaoErro: function () {
		document.body.style.cursor = ""
		alert("Erro na conexão")
	}})
}

// Vai para uma refeição do histórico
function irHistorico(i) {
	montar(dados.info.historico[i], true)
}

// Vota na refeição
function votar(num) {
	if (!dados.refeicaoAtual.podeVotar || !pedirRA())
		return;
	document.body.style.cursor = "progress"
	Ajax({url: url+"votar",
	dados: {refeicao: dados.refeicaoAtual.id, ra: dados.ra, voto: num},
	metodo: "POST",
	retorno: "json",
	funcao: function (resultado) {
		document.body.style.cursor = ""
		if (resultado) {
			carregar()
		}
	}})
}

// Carrega a refeição com base no "delta" global
function carregar() {
	document.body.style.cursor = "progress"
	if (ajax)
		ajax.abortar()
	ajax = Ajax({url: url+"cardapio",
	dados: {delta: dados.delta, ra: dados.ra},
	retorno: "json",
	funcao: montar,
	funcaoErro: function () {
		dados.delta -= sentido
		document.body.style.cursor = ""
		alert("Erro na conexão")
	}})
}
*/
