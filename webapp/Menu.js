// Gerencia o funcionamento dos menus
// Menu.abrir(opcoes) retorna um ouvinte para ser usando, geralmente, como:
// document.getElementById("botao").onclick = Menu.abrir([["A", funcA], ["B", funcB]])
// Menu.fechar() fecha imediatamente o menu aberto
var Menu = (function () {
	var divMenu = null
	window.addEventListener("load", function () {
		document.body.addEventListener("click", Menu.fechar)
	})
	
	return {abrir: function (opcoes) {
		return function (evento) {
			var i, subdiv, fechar
			
			// Monta a div
			Menu.fechar()
			divMenu = document.createElement("div")
			divMenu.classList.add("menu")
			document.body.appendChild(divMenu)
			
			// Insere os botões
			for (i=0; i<opcoes.length; i++) {
				subdiv = document.createElement("div")
				subdiv.innerHTML = opcoes[i][0]
				subdiv.onclick = opcoes[i][1]
				divMenu.appendChild(subdiv)
			}
			
			// Posiciona
			if (evento.pageX+divMenu.offsetWidth > document.body.offsetWidth)
				divMenu.style.left = (evento.pageX-divMenu.offsetWidth)+"px"
			else
				divMenu.style.left = evento.pageX+"px"
			if (evento.pageY+divMenu.offsetHeight > document.body.offsetHeight)
				divMenu.style.top = (evento.pageY-divMenu.offsetHeight)+"px"
			else
				divMenu.style.top = evento.pageY+"px"
			
			evento.stopPropagation()
		}
	}, fechar: function () {
		if (divMenu) {
			document.body.removeChild(divMenu)
			divMenu = null
		}
	}}
})()