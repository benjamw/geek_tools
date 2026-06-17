import { Ruler } from './ruler.js';
import { generateRandomIP } from './rand_ip.js';
import { initButtons } from './buttons.js';

function sizeRuler() {
	const canvas = document.getElementById('ruler');
	canvas.width = canvas.parentElement.clientWidth - 30;
}

function initRuler() {
	sizeRuler();

	const ruler = new Ruler();
	ruler.draw();

	// recalculate on input
	['mm', 'cm', 'inch', 'finch'].forEach(function (id) {
		const el = document.getElementById(id);
		el.addEventListener('change', function () { ruler.calc(this); });
		el.addEventListener('keyup', function () { ruler.calc(this); });
	});

	// redraw on graduation changes
	document.getElementById('fractions').addEventListener('change', function () { ruler.draw(); });
	document.getElementById('mark18').addEventListener('change', function () { ruler.draw(); });

	window.addEventListener('resize', function () {
		sizeRuler();
		ruler.draw();
	});
}

function initRandomIp() {
	document.getElementById('rand_ipv4').value = generateRandomIP(4);
	document.getElementById('rand_ipv6').value = generateRandomIP(6);
}

initRuler();
initRandomIp();
initButtons();
