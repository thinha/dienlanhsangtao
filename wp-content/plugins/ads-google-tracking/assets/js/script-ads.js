
jQuery(document).ready(function ($) {

	'use strict';

	detectFromGooleAds(event);

	function detectFromGooleAds(event) {

		moment.locale('vn');
		const date = new Date();
		const time = date.getTime();
		const hasChange = Cookies.get('hasChange');
		const hasSessionStorage = sessionStorage.getItem('change');
		const getCookies = Cookies.get('atFirstVisit');

		if( getCookies === undefined) {
			Cookies.set('atFirstVisit', time, { expires: 365 });
		} 
		if (hasSessionStorage === null ){
			Cookies.set('atFirstVisit', time, { expires: 365 });
		}

		const timeSession = moment(time - parseInt(getCookies)).format('hh:mm:ss');


		switch(true){
			case hasChange === undefined && hasSessionStorage === null:{
				Cookies.set('hasChange', 'A', { expires: 365 });
				sessionStorage.setItem('change', 'A');
				break;
			}
			case hasChange === 'A' && hasSessionStorage === null && Date.parse('01/01/2021 '+timeSession) > Date.parse('01/01/2021 00:00:03'):{
				sessionStorage.setItem('change', 'B');
				Cookies.set('hasChange', 'B', { expires: 365 });
				break;
			}
			case hasChange === 'B' && hasSessionStorage === null && Date.parse('01/01/2021 '+timeSession) > Date.parse('01/01/2021 00:00:03'):{
				sessionStorage.setItem('change', 'A');
				Cookies.set('hasChange', 'A', { expires: 365 });
				break;
			}
		}
		const  hasChanged = Cookies.get('hasChange');
		$.ajax({
			url: ajax_object.ajax_url,
			type:'POST',
			data: {
				action:'tracking',
				cookies:hasChanged
			},
			success: function(response){
				
			}
		});

	}

});




