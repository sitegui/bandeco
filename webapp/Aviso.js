// Controla os avisos na tela
// Aviso.avisar(string str, int tempo=0)
// Aviso.falhar(string str, int tempo=0)
// Aviso.esconder()
var Aviso = (function () {
	var intervalo = null, mostrar = function (str, tempo) {
		get("status").textContent = str
		get("status").classList.remove("escondido")
		clearInterval(intervalo)
		if (tempo)
			intervalo = setTimeout(function () {
				Aviso.esconder()
			}, tempo)
	}	
	
	return {
		avisar: function (str, tempo) {
			mostrar(str, tempo)
			get("status").classList.remove("falha")
		}, falhar: function (str, tempo) {
			mostrar(str, tempo)
			get("status").classList.add("falha")
		}, esconder: function () {
			clearInterval(intervalo)
			get("status").classList.add("escondido")
		}
	}
})()