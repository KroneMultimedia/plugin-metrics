var KRNM = {}
KRNM.requestStart = new Date();


jQuery(window).load(function() {
	KRNM.requestEnd = new Date();



	KRNM.loadTime = KRNM.requestEnd.getTime() - KRNM.requestStart.getTime();
	console.log("LOAD TIME:" + KRNM.loadTime);

	var payload = {
		action: 'krn_metrics_track',
		metric: {
			name:  'pageload',
			category: pagenow,
			type: "timing",
			post_type: typenow,
			value: KRNM.loadTime
		}

	};
	console.log("TRACK: ", payload)
	jQuery.ajax({
		type: 'POST',
		url: ajaxurl,
		data: payload,
		success: function (data, textStatus, XMLHttpRequest) {
				console.log("STORED")
		},
		error: function (XMLHttpRequest, textStatus, errorThrown) {
			console.log("FAILED")
		}
});

})