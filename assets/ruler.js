// length conversion
let Ruler = class {
	constructor() {
		this.ofractions = document.getElementById('fractions');
		this.oinch = document.getElementById('inch');
		this.ofinch = document.getElementById('finch');
		this.omm = document.getElementById('mm');
		this.ocm = document.getElementById('cm');
		this.omsg = document.getElementById('msg');
		this.oformula = document.getElementById('formula');

		this.dpi_x = 102.4; // for my monitor... YMMV

		this.ppcm = this.dpi_x / 2.54;
		this.c = document.getElementById("ruler");
		this.cxt = this.c.getContext("2d");
		this.w = this.c.clientWidth;
		this.begin_x = 20;
		this.BL_cm = 0.5;
		this.BL_inch = 129.5;
		this.begin_cm = 0;
	}

	set_fractional_inch(inch) {
		let fractions = this.ofractions.value;
		let fra_in = Math.floor(inch);

		let numerator = Math.round((inch - fra_in) / (1 / fractions));
		let denominator = fractions;

		let sTemp;

		while ((0 === numerator % 2) && (0 === denominator % 2)) {
			numerator /= 2;
			denominator /= 2;
		}

		if (numerator == 1 && denominator == 1) {
			fra_in += 1;
			numerator = 0;
		}

		if (fra_in > 0) {
			sTemp = fra_in;
		}
		else {
			sTemp = '';
		}

		if (numerator > 0) {
			if (fra_in > 0) {
				sTemp += ' ';
			}

			sTemp += numerator + '/' + denominator
		}

		this.ofinch.value = sTemp;
	}

	set_inch(inch) {
		if (isNaN(inch)) {
			this.oinch.value = '';
			this.omm.value = '';
			this.ocm.value = '';
		}
		else {
			this.oinch.value = inch;
			this.omm.value = Math.round(inch * 25.4 * 10) / 10;
			this.ocm.value = Math.round(inch * 2.54 * 100) / 100;
		}
	}

	frac_to_dec(frac) {
		let f, numerator, denominator, ir;
		let fa, finch2, fa2;

		frac = frac.trim();
		if (frac.indexOf(' ') < 0) {
			f = frac.split('/');

			if (f.length == 1) {
				return parseInt(frac);
			}
			else if (f.length == 2) {
				numerator = f[0];
				denominator = f[1];
				if ((numerator != '') && (denominator != '') && parseInt(denominator) > 0 && parseInt(numerator) > 0) {
					ir = Math.round((numerator / denominator) * 10000000) / 10000000;
					return ir;
				}
			}
		}
		else {
			fa = frac.split(" ");
			ir = parseInt(fa[0]);
			finch2 = fa[1];
			fa2 = finch2.split('/');

			if (fa2.length == 2) {
				numerator = fa2[0];
				denominator = fa2[1];
				if ((numerator != '') && (denominator != '') && parseInt(denominator) > 0 && parseInt(numerator) > 0) {
					ir = Math.round((ir + numerator / denominator) * 10000000) / 10000000;
				}
			}

			return ir;
		}

		return 0;
	}

	calc(t) {
		let patFraction = /\d\/\d/;
		let v = t.value.trim().replace('/"/g', '');
		let short_over, inch2, sFinch, sTmpf, sTmp, cm;

		if (patFraction.test(v)) {
			v = this.frac_to_dec(v);
		}
		if (t == this.omm) { // input mm
			if (v == '' || isNaN(v)) {
				this.ocm.value = '';
				this.oinch.value = '';
				this.ofinch.value = '';
				this.oformula.style.display = "none";
				this.omsg.innerHTML = 'Please enter a valid millimeter number';
			}
			else {
				this.ocm.value = Math.round(v / 10 * 1000000000000) / 1000000000000;
				let inch = Math.round(v / 25.4 * 100) / 100;
				this.oinch.value = inch;
				this.set_fractional_inch(inch);
				if (patFraction.test(this.ofinch.value) && (this.ofinch.value !== this.oinch.value)) {
					//short or over fraction
					short_over = "";
					let idxSpace = this.ofinch.value.indexOf(' ');
					if (idxSpace > -1) {
						let wholenumber = parseFloat(this.ofinch.value.substring(0, idxSpace));
						let arrFrac = this.ofinch.value.substring(idxSpace + 1).split("/", 2);
						inch2 = wholenumber + arrFrac[0] / arrFrac[1];
						let middle = 1 / this.ofractions.value / 3;
						if (inch > inch2 && inch - inch2 > middle) {
							short_over = "little over ";
						}
						else if (inch < inch2 && inch2 - inch > middle) {
							short_over = "just short of ";
						}
					}
					else {
						let arrFrac = this.ofinch.value.split("/", 2);
						inch2 = arrFrac[0] / arrFrac[1];
						let middle = 1 / this.ofractions.value / 3;
						if (inch > inch2 && inch - inch2 > middle) {
							short_over = "little over ";
						}
						else if (inch < inch2 && inch2 - inch > middle) {
							short_over = "just short of ";
						}
					}
					sFinch = " &nbsp; = &nbsp; " + short_over + this.ofinch.value + " inch" + (this.ofinch.value == "1" ? "" : "es");
				}
				else {
					sFinch = "";
				}
				this.omsg.innerHTML = this.omm.value + ' mm &nbsp; = &nbsp; ' + this.ocm.value + ' cm &nbsp; = &nbsp; ' + this.oinch.value + ' inch' + (this.oinch.value == "1" ? "" : "es") + sFinch;
				sTmpf = '<li>' + this.omm.value + ' mm &divide; 10 = ' + this.ocm.value + ' cm</li>';
				sTmpf += '<li>' + this.omm.value + ' mm &divide; 25.4 = ' + (this.omm.value / 25.4) + ' in</li>';
				this.oformula.innerHTML = '<ul>' + sTmpf + '</ul>';
				this.oformula.style.display = "block";
			}
		}
		else if (t == this.ocm) { // input cm
			if (v == '' || isNaN(v)) {
				this.omm.value = '';
				this.oinch.value = '';
				this.ofinch.value = '';
				this.oformula.style.display = "none";
				this.omsg.innerHTML = 'Please enter a valid centimeter number';
			}
			else {
				this.omm.value = Math.round(v * 10 * 1000000000000) / 1000000000000;
				let inch = Math.round(v / 2.54 * 100) / 100;
				this.oinch.value = inch;
				this.set_fractional_inch(inch);
				if (patFraction.test(this.ofinch.value) && (this.ofinch.value != this.oinch.value)) {
					sFinch = " &nbsp; = &nbsp; " + this.ofinch.value + " inch" + (this.ofinch.value == "1" ? "" : "es");
				}
				else {
					sFinch = "";
				}
				this.omsg.innerHTML = this.ocm.value + ' cm &nbsp; = &nbsp; ' + this.omm.value + ' mm &nbsp; = &nbsp; ' + this.oinch.value + ' inch' + (this.oinch.value == "1" ? "" : "es") + sFinch;
				sTmpf = '<li>' + this.ocm.value + ' cm &times; 10 mm = ' + this.omm.value + ' mm</li>';
				sTmpf += '<li>' + this.ocm.value + ' cm &divide; 2.54 in = ' + (this.ocm.value / 2.54) + ' in</li>';
				this.oformula.innerHTML = '<ul>' + sTmpf + '</ul>';
				this.oformula.style.display = "block";
			}
		}
		else if (t == this.oinch) { // input inch
			if (v == '' || isNaN(v)) {
				this.omm.value = '';
				this.ocm.value = '';
				this.ofinch.value = '';
				this.oformula.style.display = "none";
				this.omsg.innerHTML = 'Please enter a valid inch number';
			}
			else {
				this.omm.value = Math.round(v * 25.4 * 10) / 10;
				this.ocm.value = Math.round(v * 2.54 * 100) / 100;
				this.set_fractional_inch(v);
				if (patFraction.test(this.ofinch.value) && (this.ofinch.value != this.oinch.value)) {
					sFinch = " &nbsp; = &nbsp; " + this.ofinch.value + " inch" + (this.ofinch.value == "1" ? "" : "es");
				}
				else {
					sFinch = "";
				}
				this.omsg.innerHTML = this.oinch.value + ' inch' + (this.oinch.value == "1" ? "" : "es") + sFinch + " &nbsp; = &nbsp; " + this.omm.value + ' mm &nbsp; = &nbsp; ' + this.ocm.value + ' cm';
				sTmpf = '<li>' + this.oinch.value + ' in &times; 25.4 = ' + this.omm.value + ' mm</li>';
				sTmpf += '<li>' + this.oinch.value + ' in &times; 2.54  = ' + (this.oinch.value * 2.54) + ' cm</li>';
				this.oformula.innerHTML = '<ul>' + sTmpf + '</ul>';
				this.oformula.style.display = "block";
			}
		}
		else if (t == this.ofinch) {
			if (v == '' || isNaN(v)) {
				this.omm.value = '';
				this.ocm.value = '';
				this.oinch.value = '';
				this.oformula.style.display = "none";
				this.omsg.innerHTML = 'Please enter a valid inch number';
			}
			else {
				this.set_inch(v);
				this.omsg.innerHTML = this.ofinch.value + ' inch' + (this.ofinch.value == "1" ? "" : "es") + (this.oinch.value == this.ofinch.value ? "" : " &nbsp; = &nbsp; " + this.oinch.value + " inch" + (this.oinch.value == "1" ? "" : "es")) + " &nbsp; = &nbsp; " + this.omm.value + ' mm &nbsp; = &nbsp; ' + this.ocm.value + ' cm';
				if (this.ofinch.value != this.oinch.value) {
					sTmp = ' &nbsp; = &nbsp; ' + this.oinch.value + ' in';
				}
				else {
					sTmp = '';
				}
				sTmpf = '<li>' + this.ofinch.value + ' in' + sTmp + ' &times; 25.4  = ' + this.omm.value + ' mm</li>';
				sTmpf += '<li>' + this.ofinch.value + ' in' + sTmp + ' &times; 2.54  = ' + (this.oinch.value * 2.54) + ' cm</li>';
				this.oformula.innerHTML = '<ul>' + sTmpf + '</ul>';
				this.oformula.style.display = "block";
			}
		}
		this.draw();
		cm = parseFloat(this.ocm.value);
		if ((cm != 'NaN') && (cm > 0)) {
			this.mark((this.ocm.value - this.begin_cm) * this.ppcm);
		}
	}

	draw() {
		let ruler_length, cm, Lh, s2, s10, s4, s8, s16, s32, begin_inch, inch_offset;

		//move to mark of current
		ruler_length = this.c.width / this.ppcm;
		cm = parseInt(document.getElementById('cm').value);
		if ((cm != 'NaN') && (cm > ruler_length - 1)) {
			this.begin_cm = cm - Math.floor(ruler_length / 2 - 5);
		}
		else {
			this.begin_cm = 0;
		}
		this.cxt.setTransform(1, 0, 0, 1, 0, 0);
		this.cxt.clearRect(0, 0, this.c.width, this.c.height);
		//ruler for cm
		this.cxt.strokeStyle = '#ffffff';
		this.cxt.lineWidth = 1;
		this.cxt.beginPath();
		this.cxt.moveTo(0, this.BL_cm);
		this.cxt.lineTo(this.w, this.BL_cm);
		this.cxt.stroke();
		for (let i = this.begin_x, j = this.begin_cm; i <= this.w; i = i + this.ppcm, j++) {
			Lh = this.BL_cm + 35;
			this.cxt.beginPath();
			this.cxt.strokeStyle = '#ffffff';
			this.cxt.fillStyle = '#ffffff';
			this.cxt.lineWidth = 1;
			this.cxt.moveTo(i, Lh);
			this.cxt.lineTo(i, this.BL_cm);
			this.cxt.stroke();
			this.cxt.font = "20px Arial";
			if (j < 10) {
				this.cxt.fillText(j, i - 6, Lh + 20);
			}
			else {
				this.cxt.fillText(j, i - 11, Lh + 20);
			}
		}
		s2 = this.ppcm / 2;
		for (let i = this.begin_x, j = 0; i <= this.w; i = i + s2, j++) {
			if (j % 2 == 0) continue;
			Lh = this.BL_cm + 25;
			this.cxt.beginPath();
			this.cxt.strokeStyle = '#ffffff';
			this.cxt.lineWidth = 1;
			this.cxt.moveTo(i, Lh);
			this.cxt.lineTo(i, this.BL_cm);
			this.cxt.stroke();
		}
		s10 = this.ppcm / 10;
		for (let i = this.begin_x, j = 0; i <= this.w; i = i + s10, j++) {
			if ((j % 5 == 0) || (j % 10 == 0)) continue;
			Lh = this.BL_cm + 15;
			this.cxt.beginPath();
			this.cxt.strokeStyle = '#ffffff';
			this.cxt.lineWidth = 1;
			this.cxt.moveTo(i, Lh);
			this.cxt.lineTo(i, this.BL_cm);
			this.cxt.stroke();
		}
		//ruler for inch
		this.cxt.strokeStyle = '#ffffff';
		this.cxt.lineWidth = 1;
		this.cxt.beginPath();
		this.cxt.moveTo(0, this.BL_inch);
		this.cxt.lineTo(this.w, this.BL_inch);
		this.cxt.stroke();
		if (this.begin_cm != 0) {
			begin_inch = Math.ceil(this.begin_cm / 2.54);
			inch_offset = begin_inch * this.dpi_x - this.begin_cm * this.ppcm;
		}
		else {
			begin_inch = 0;
			inch_offset = 0;
		}
		for (let i = this.begin_x + inch_offset, j = begin_inch; i <= this.w; i = i + this.dpi_x, j++) {
			Lh = this.BL_inch - 35;
			this.cxt.beginPath();
			this.cxt.fillStyle = '#ffffff';
			this.cxt.lineWidth = 1;
			this.cxt.moveTo(i, Lh);
			this.cxt.lineTo(i, this.BL_inch);
			this.cxt.stroke();
			this.cxt.font = "20px Arial";
			if (j < 10) {
				this.cxt.fillText(j, i - 6, Lh - 5);
			}
			else {
				this.cxt.fillText(j, i - 12, Lh - 5);
			}
		}
		s2 = this.dpi_x / 2;
		for (let i = this.begin_x + inch_offset, j = 0; i <= this.w; i = i + s2, j++) {
			if (j % 2 == 0) continue;
			Lh = this.BL_inch - 30;
			this.cxt.beginPath();
			this.cxt.fillStyle = '#ffffff';
			this.cxt.lineWidth = 1;
			this.cxt.moveTo(i, Lh);
			this.cxt.lineTo(i, this.BL_inch);
			this.cxt.stroke();
			this.cxt.font = "16px Arial";
			this.cxt.fillText('½', i - 7, Lh - 5);
		}
		s4 = this.dpi_x / 4;
		for (let i = this.begin_x + inch_offset, j = 0; i <= this.w; i = i + s4, j++) {
			if ((j % 2 == 0) || (j % 4 == 0)) continue;
			Lh = this.BL_inch - 25;
			this.cxt.beginPath();
			this.cxt.fillStyle = '#ffffff';
			this.cxt.lineWidth = 1;
			this.cxt.moveTo(i, Lh);
			this.cxt.lineTo(i, this.BL_inch);
			this.cxt.stroke();
			this.cxt.font = "12px Arial";
			if (j % 4 == 1) {
				this.cxt.fillText('¼', i - 7, Lh - 5);
			}
			else if (j % 4 == 3) {
				this.cxt.fillText('¾', i - 7, Lh - 5);
			}
		}
		s8 = this.dpi_x / 8;
		for (let i = this.begin_x + inch_offset, j = 0; i <= this.w; i = i + s8, j++) {
			if ((j % 2 == 0) || (j % 4 == 0)) continue;
			Lh = this.BL_inch - 18;
			this.cxt.beginPath();
			this.cxt.fillStyle = '#ffffff';
			this.cxt.lineWidth = 1;
			this.cxt.moveTo(i, Lh);
			this.cxt.lineTo(i, this.BL_inch);
			this.cxt.stroke();
			if (document.getElementById('mark18').checked == true) {
				this.cxt.save();
				this.cxt.font = "12px Arial";
				this.cxt.scale(0.8, 1);
				if (j % 8 == 1) {
					this.cxt.fillText('⅛', (i - 7) / 0.8, Lh - 1);
				}
				else if (j % 8 == 3) {
					this.cxt.fillText('⅜', (i - 6) / 0.8, Lh - 1);
				}
				else if (j % 8 == 5) {
					this.cxt.fillText('⅝', (i - 6) / 0.8, Lh - 1);
				}
				else if (j % 8 == 7) {
					this.cxt.fillText('⅞', (i - 6) / 0.8, Lh - 1);
				}
				this.cxt.restore();
			}
		}
		if (document.getElementById('fractions').value > 8) {
			s16 = this.dpi_x / 16;
			for (let i = this.begin_x + inch_offset, j = 0; i <= this.w; i = i + s16, j++) {
				if ((j % 2 == 0) || (j % 4 == 0) || (j % 8 == 0)) continue;
				Lh = this.BL_inch - 15;
				this.cxt.beginPath();
				this.cxt.fillStyle = '#ffffff';
				this.cxt.lineWidth = 1;
				this.cxt.moveTo(i, Lh);
				this.cxt.lineTo(i, this.BL_inch);
				this.cxt.stroke();
			}
		}
		if (document.getElementById('fractions').value > 16) {
			s32 = this.dpi_x / 32;
			for (let i = this.begin_x + inch_offset, j = 0; i <= this.w; i = i + s32, j++) {
				if ((j % 2 == 0) || (j % 4 == 0) || (j % 8 == 0) || (j % 16 == 0)) continue;
				Lh = this.BL_inch - 10;
				this.cxt.beginPath();
				this.cxt.fillStyle = '#ffffff';
				this.cxt.lineWidth = 1;
				this.cxt.moveTo(i, Lh);
				this.cxt.lineTo(i, this.BL_inch);
				this.cxt.stroke();
			}
		}
		this.cxt.save();
		this.cxt.translate(0, 0);
		this.cxt.rotate(90 * Math.PI / 180);
		this.cxt.fillStyle = '#ffffff';
		this.cxt.font = "12px Arial";
		this.cxt.fillText('MM CM', 3, -2);
		this.cxt.fillText("INCH", 94, -2);
		this.cxt.restore();
		this.cxt.closePath();
	}

	mark(px) {
		this.cxt.strokeStyle = '#00ff00';
		this.cxt.lineWidth = 1;
		this.cxt.beginPath();
		this.cxt.moveTo(this.begin_x + px, 0);
		this.cxt.lineTo(this.begin_x + px, 130);
		this.cxt.stroke();
	}
}

let r;
