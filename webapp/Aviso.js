// Controla os avisos na tela
// Aviso.avisar(string str, int tempo=0)
// Aviso.falhar(string str, int tempo=0)
// Aviso.esconder()
var Aviso = (function () {
	var intervalo = null, mostrar = function (str, tempo) {
		get("status").textContent = str
		clearInterval(intervalo)
		if (tempo)
			intervalo = setTimeout(function () {
				Aviso.esconder()
			}, tempo)
	}	
	
	return {
		avisar: function (str, tempo) {
			mostrar(str, tempo)
			get("status").className = ""
		}, falhar: function (str, tempo) {
			mostrar(str, tempo)
			get("status").className = "falha"
		}, esconder: function () {
			clearInterval(intervalo)
			get("status").className = "escondido"
		}
	}
})()