$(document).ready(function(){
	var dump = false;
	var indexed = [];

	$('body').append(
		'<div id="player1">&nbsp;</div><div id="player2">&nbsp;</div><div id="bullet">&nbsp;</div>'
	);

	function shoot(from, to){
		var otmp = $('#bullet').css('width').split('px')[0]*1;
		var offset = otmp / 2;
		var offset2 = ($('#player' + to).css('width').split('px')[0]*1 - otmp) / 2;
		var o = offset + offset2;

		var pfmt = $('#player' + from).css('marginTop').split('px')[0]*1 + o;
		var ptmt = $('#player' + to).css('marginTop').split('px')[0]*1 + o;

		var pfml = $('#player' + from).css('marginLeft').split('px')[0]*1 + o;
		var ptml = $('#player' + to).css('marginLeft').split('px')[0]*1 + o;

		// virtual central zero-point
		var ceml = pfml + ((pfmt) / (pfmt + ptmt)) * (ptml - pfml) + o + offset;
		var cemt = 2*o; // for now it is fixed

		// making vitual triangle
		var cath1 = Math.sqrt(Math.pow(Math.abs(pfml - ptml), 2) + Math.pow(Math.abs(pfmt - ptmt), 2));
		var cath2 = Math.sqrt(Math.pow(Math.abs(pfml - ceml), 2) + Math.pow(Math.abs(pfmt - cemt), 2));
		var cath3 = Math.sqrt(Math.pow(Math.abs(ceml - ptml), 2) + Math.pow(Math.abs(cemt - ptmt), 2));
		var tr_p = (cath1 + cath2 + cath3) / 2;
		var tr_area = Math.sqrt(tr_p * (tr_p - cath1) * (tr_p - cath2) * (tr_p - cath3));
		var sin_cath1_angle = 2 * tr_area / cath2 / cath3;

		// making virtual circle
		var circle_R = Math.round(cath1 / 2 / sin_cath1_angle);

		make_circles(pfmt, pfml, circle_R, 1);
		make_circles(ptmt, ptml, circle_R, 2);
		make_circles(cemt, ceml, circle_R, 3);

		var circle_O = get_center();
//		make_circles(top margin, left margin, circle radius);
		make_circles(circle_O[0], circle_O[1], circle_R);

		var calc1 = pfmt + o;
		var calc2 = ptml - offset;
		var calc3 = pfmt + ptmt - 4*o - offset2;

		var animation_length = circle_O[1];
		if(circle_O[1] - circle_R < pfml){
			animation_length += 2 * Math.abs(pfml - (circle_O[1] - circle_R));
		}

		if(circle_O[1] + circle_R > ptml){
			animation_length += 2 * Math.abs(circle_O[1] + circle_R - ptml);
		}

		prepare_animation(animation_length);

//		$('#bullet').animate({
//			marginLeft: calc2
//		}, {
//			duration: 3000,
//			step: function(now){
//				$(this).css('marginTop', Math.round(Math.abs(calc1 - Math.abs(now / calc2) * calc3)));
//			}
//		});

		var bullet = $('#bullet');
		var duration = 3000;

		$('#anim_len').animate({
			width: animation_length
		}, {
			duration: duration,
			step: function(now){
				var tmp = Math.abs(circle_R - now / animation_length) * Math.PI; // + 0.655;
				var tmp_top = Math.round(circle_R * Math.sin(tmp));
				var tmp_left = Math.round(circle_R * Math.cos(tmp));

				var start_top = circle_O[0] - tmp_top;
				var start_left = circle_O[1] + tmp_left;

				start_top = Math.round(start_top);
				start_left = Math.round(start_left);

				bullet.css('marginTop', start_top);
				bullet.css('marginLeft', start_left);
			}
		});
	}

	function make_circles(mt, ml, circle_R, w){
		circle_R = Math.round(circle_R);

		if(dump){
			$('body').append(
				'<div class="dump dumpc" style="margin-top: ' + (mt) + 'px; margin-left: ' + (ml) + 'px;">\
					&nbsp;\
				</div>'
			);
		}

		for(var i = circle_R; i >= 0; i-=0.25){
			var tmp = Math.abs((circle_R - i) / circle_R) / 2 * Math.PI;
			var tmp_top = Math.round(circle_R * Math.sin(tmp));
			var tmp_left = Math.round(circle_R * Math.cos(tmp));

			var t1 = mt - tmp_top;
			var t2 = mt + tmp_top;
			var l1 = ml + tmp_left;
			var l2 = ml - tmp_left;

			t1 = Math.round(t1);
			t2 = Math.round(t2);
			l1 = Math.round(l1);
			l2 = Math.round(l2);

			if(dump){
				// dump start
				$('body').append(
					'<div class="dump" style="margin-top: ' + (t1) + 'px; margin-left: ' + (l1) + 'px;">\
						&nbsp;\
					</div>' +
					'<div class="dump" style="margin-top: ' + (t1) + 'px; margin-left: ' + (l2) + 'px;">\
						&nbsp;\
					</div>' +
					'<div class="dump" style="margin-top: ' + (t2) + 'px; margin-left: ' + (l1) + 'px;">\
						&nbsp;\
					</div>' +
					'<div class="dump" style="margin-top: ' + (t2) + 'px; margin-left: ' + (l2) + 'px;">\
						&nbsp;\
					</div>'
				);
				// dump end
			}

			add_elem(t1, l1, w);
			add_elem(t1, l2, w);
			add_elem(t2, l1, w);
			add_elem(t2, l2, w);
		}
	}

	function add_elem(top, left, w){
		if(indexed['x' + top + 'x' + left] == undefined){
			indexed['x' + top + 'x' + left] = [];
		}

		indexed['x' + top + 'x' + left].push(w);
	}

	function get_center(){
		for(var i in indexed){
			if(jQuery.inArray(1, indexed[i]) != -1 && jQuery.inArray(2, indexed[i]) != -1 && jQuery.inArray(3, indexed[i]) != -1){
				return [i.split('x')[1]*1, i.split('x')[2]*1];
			}
		}

		return false;
	}

	function prepare_animation(animation_length){
		$('body').append('<div id="anim_len" class="hidden">' + animation_length + '</div>');
	}

	var from = 1;
	shoot(from, from % 2 + 1);
});
