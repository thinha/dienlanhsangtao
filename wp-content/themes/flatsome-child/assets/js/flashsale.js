jQuery(function() {
	if (!document.getElementById("count_down")) return;
	const start_hour =  !(flashsale_object.start_hour)?0:flashsale_object.start_hour ;
	const start_minute =  !(flashsale_object.start_minute)?0:flashsale_object.start_minute ;
	const cron_hour =  !(flashsale_object.cron_hour)?0:flashsale_object.cron_hour ;
	const cron_minute =  !(flashsale_object.cron_minute)?0:flashsale_object.cron_minute ;
	const end_days =  !(flashsale_object.end_days)?0:flashsale_object.end_days ;
	const cron_days =  !(flashsale_object.cron_days)?0:flashsale_object.cron_days ;
	const cron_months =  !(flashsale_object.cron_months)?0:flashsale_object.cron_months;

	// Set the date we're counting down to
	const today = new Date();
	const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
	const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0).getDate();
	const months = [1, 2, 3, 4, 5, 6,7, 8, 9, 10, 11, 12];
	
	const check_update =  '1';

	let day = cron_days;
	let month = parseInt(cron_months) - 1;
	let year = today.getFullYear();
	let hour = cron_hour;
	let minute = cron_minute;



	if( cron_hour === 24 ){
		if( lastDay === day ){
			day = 1;
			if( month === 12 ){
				month = 1;
				year = year + 1;
			} else {
				month = month + 1;
			}
		} else {
			day = day + 1;
			hour = 0;
		}
	}

	var countDownDate = new Date(year, month, day, hour, minute, 0).getTime();


	if(check_update == 1){
		// Update the count down every 1 second
		var x = setInterval(function() {

		  // Get today's date and time
		  var now = new Date().getTime();

		  // Find the distance between now and the count down date
		  var distance = countDownDate - now;

		  // Time calculations for days, hours, minutes and seconds
		  var days = Math.floor(distance / (1000 * 60 * 60 * 24));
		  var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
		  var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
		  var seconds = Math.floor((distance % (1000 * 60)) / 1000);
		  if (seconds < 10)
		  	seconds = '0'+seconds;

		  if (hours < 10)
		  	hours = '0'+hours;

		  if (minutes < 10)
		  	minutes = '0'+minutes;

		  // Display the result in the element with id="demo"
		  document.getElementById("count_down").innerHTML = '<span class="day">'+days+ ' </span> : <span class="hour">'+hours+ ' </span> : <span class="minute">' + minutes + '</span> : <span class="second">' + seconds + '</span>';

		  // If the count down is finished, write some text
		  if (distance < 0) {
		    clearInterval(x);
		    document.getElementById("count_down").innerHTML = "EXPIRED";
		    setTimeout(function(){
		
		    }, 10000);
		   
		  }
		}, 1000);
	}

});

jQuery(function() {
	jQuery('.multiple-items').slick({
	  infinite: true,
	  slidesToShow: 6,
	  slidesToScroll: 2,
	  arrows: false,
	  autoplay: true,
	  responsive: [
        {
          breakpoint: 1200,
          settings: {
            slidesToShow: 6,
          }
        },
        {
          breakpoint: 768,
          settings: {
            slidesToShow: 2,
          }
        },
        {
          breakpoint: 320,
          settings: {
            slidesToShow: 2,
          }
        }
      ]
	});
});
