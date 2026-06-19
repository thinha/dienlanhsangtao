var someone_setting = [
	

	{
		product_title: "Tủ đông Darling DMF-1179ASI Smart Inverter 2 cánh 1300l",
		product_url: "https://dienlanhsangtao.com/san-pham/tu-dong-2-canh-1200l-darling-smart-inverter-dmf-1179-asi",
		product_img: "https://dienlanhsangtao.com/wp-content/uploads/2020/07/tu-dong-darling-1179-asi-510x611.png",
		product_percent: "0"
	},

	{
		product_title: "Tủ đông Darling DMF-4799ASI 470l Smart Inverter",
		product_url: "https://dienlanhsangtao.com/san-pham/tu-dong-470l-darling-smart-inverter-dmf-4799-asi/",
		product_img: "https://dienlanhsangtao.com/wp-content/uploads/2020/07/tu-dong-darling-4799-asi-510x611.png",
		product_percent: "0"
	},


	{
		product_title: "Tủ đông Darling DMF-1579ASI 3 cánh 1700L Inverter",
		product_url: "https://dienlanhsangtao.com/san-pham/tu-dong-3-canh-1700l-darling-smart-inverter-dmf-1579-asi/",
		product_img: "https://dienlanhsangtao.com/wp-content/uploads/2020/07/tu-dong-darling-1579-asi-510x611.png",
		product_percent: "0"
	},

	{
		product_title: "Tủ đông Darling DMF-7779ASI Smart Inverter 2 cánh 770 lít",
		product_url: "https://dienlanhsangtao.com/san-pham/tu-dong-2-canh-600l-darling-smart-inverter-dmf-7779-asi/",
		product_img: "https://dienlanhsangtao.com/wp-content/uploads/2020/07/tu-dong-darling-7779-asi-510x611.png",
		product_percent: "0"
	},

	{
		product_title: "Tủ đông Darling DMF-8779ASI Smart Inverter 2 cánh 800l",
		product_url: "https://dienlanhsangtao.com/san-pham/tu-dong-2-canh-800l-darling-smart-inverter-dmf-8779-asi/",
		product_img: "https://dienlanhsangtao.com/wp-content/uploads/2020/07/tu-dong-darling-8779-asi-510x611.png",
		product_percent: "0"
	},
	{
		product_title: "Tủ đông Darling DMF-9779ASI 970L Smart Inverter 2 cánh",
		product_url: "https://dienlanhsangtao.com/san-pham/tu-dong-2-canh-1000l-darling-smart-inverter-dmf-9779-asi/",
		product_img: "https://dienlanhsangtao.com/wp-content/uploads/2020/07/tu-dong-darling-9779-asi-510x611.png",
		product_percent: "0"
	},
	{
		product_title: "Tủ đông mát Darling DMF-7699WSI Smart Inverter 800L",
		product_url: "https://dienlanhsangtao.com/san-pham/tu-dong-mat-800l-darling-smart-inverter-dmf-7699-wsi/",
		product_img: "https://dienlanhsangtao.com/wp-content/uploads/2020/07/tu-dong-mat-7699wsi-1-1-510x389.jpg.webp",
		product_percent: "0"
	},
]

jQuery(function() {

	getProduct();
    
    
	function getProduct() {
		var num = Math.floor(Math.random() * someone_setting.length);

		jQuery(".someone__product-title").text(someone_setting[num].product_title).attr('url',someone_setting[num].product_url);
		jQuery(".someone__product-img").attr('src', someone_setting[num].product_img);
		if (someone_setting[num].product_percent != '0'){
			jQuery(".someone__product-percent").text(someone_setting[num].product_percent).parent().show();
		} else{
			jQuery(".someone__product-percent").parent().hide();
		}
		var mytimeAgo = ['1 phút trước', '5 phút trước', '10 phút trước', '12 phút trước', '14 phút trước', '16 phút trước', '18 phút trước', '20 phút trước', '25 phút trước', '30 phút trước', '35 phút trước', '40 phút trước','42 phút trước','45 phút trước', '50 phút trước', '1 giờ trước'];  
		var randomlytimeAgo = Math.floor(Math.random() * mytimeAgo.length);
		var currentmytimeAgo = mytimeAgo[randomlytimeAgo];
		jQuery(".timeAgo").text(currentmytimeAgo+""); 
		var myLocation = ['Quận Tân Bình', 'Hồ Chí Minh', 'Quận 3', 'Quận Phú Nhuận', 'Quận 2', 'Quận 1', 'Quận 10', 'Quận Bình Thạnh', 'Quận 3', 'Quận 4', 'Quận 5', 'Quận 6', 'Quận 7', 'Quận 8', 'Quận 9', 'Quận 11', 'Quận 12', 'Quận Bình Tân', 'Tân Phú', 'Gò Vấp', 'Thủ Đức', 'Bình Chánh', 'Hóc Môn', 'Nhà Bè', 'Củ Chi', 'Vũng Tàu', 'Long An', 'Cần Thơ', 'Tiền Giang', 'Vĩnh Long', 'Bình Dương', 'Đồng Nai', 'Bến Tre', 'Trà Vinh', 'Vĩnh Long', 'Đồng Tháp', 'An Giang', 'Cà Mau'];
		var randomlyLocation = Math.floor(Math.random() * myLocation.length);
		var currentmyLocation = myLocation[randomlyLocation];
		jQuery(".location").text('' + currentmyLocation);
	}

	// Loop the notification
	(function loop() {
		var rand = Math.round(Math.random() * 3000) + 10000;
		setTimeout(function() {
			changeNotification();
			loop();
		}, rand);
	}());

	// Change notification
	function changeNotification() {
		showNotification();
		setTimeout(function() {
			hideNotification();
		}, 3000) // duration
	}

	// Show notification
	function showNotification() {
		jQuery("#someone-purchased").addClass('fade-in').removeClass('fade-out');
	}

	// Hide notification
	function hideNotification() {
		jQuery("#someone-purchased").addClass('fade-out').removeClass('fade-in');
		setTimeout(function() {
			getProduct();
		}, 500)
	}

});

