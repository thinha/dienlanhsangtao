<?php 




function facebook_feature_template()
{
	ob_start();
	?>
		<div  class="fb-feature">
			<ul>
				<li>
					<div class="fb-feature_box">
					<div class="fb-post" data-href="https://www.facebook.com/cuahangsangtao.official/posts/170699245242242" data-lazy="true" data-width="270" data-show-text="true"><blockquote cite="https://www.facebook.com/cuahangsangtao.official/posts/170699245242242" class="fb-xfbml-parse-ignore"><p>Tủ đông Darling 1 ngăn lớn 1579ASI có dung tích lớn nhất 1700 lít, chứa được rất nhiều thực phẩm, rất tiện lợi cho việc...</p>Người đăng: <a href="https://www.facebook.com/cuahangsangtao.official/">Cửa Hàng Sáng Tạo   dienlanhsangtao.com</a> vào&nbsp;<a href="https://www.facebook.com/cuahangsangtao.official/posts/170699245242242">Thứ Sáu, 8 tháng 10, 2021</a></blockquote></div>
						
					</div>
					
				</li>
				<li>
					<div class="fb-feature_box">
						<div class="fb-post" data-href="https://www.facebook.com/cuahangsangtao.official/posts/133638518948315" data-lazy="true" data-width="270" data-show-text="true"><blockquote cite="https://www.facebook.com/cuahangsangtao.official/posts/133638518948315" class="fb-xfbml-parse-ignore"><p>Tủ đông Sanaky 100L miễn phí vận chuyển nội thành Tp.HCM, nhỏ gọn - tiện lợi sử dụng. Mừng sinh nhật tròn 10 năm thành...</p>Người đăng: <a href="https://www.facebook.com/cuahangsangtao.official/">Cửa Hàng Sáng Tạo   dienlanhsangtao.com</a> vào&nbsp;<a href="https://www.facebook.com/cuahangsangtao.official/posts/133638518948315">Thứ Hai, 16 tháng 8, 2021</a></blockquote></div>
						
					</div>
					
				</li>
				<li>
					<div class="fb-feature_box">
						<div class="fb-post" data-href="https://www.facebook.com/cuahangsangtao.official/posts/182053424106824" data-lazy="true" data-width="270" data-show-text="true"><blockquote cite="https://www.facebook.com/cuahangsangtao.official/posts/182053424106824" class="fb-xfbml-parse-ignore"><p>❄☃️ Tủ lạnh Aqua 130 lít thiết kế nhỏ gọn, công nghệ diệt khuẩn - khử mùi Ag+, giá bán khuyến mãi 4.300.000đ.

✳️ Đặc...</p>Người đăng: <a href="https://www.facebook.com/cuahangsangtao.official/">Cửa Hàng Sáng Tạo   dienlanhsangtao.com</a> vào&nbsp;<a href="https://www.facebook.com/cuahangsangtao.official/posts/182053424106824">Chủ nhật, 24 tháng 10, 2021</a></blockquote></div>
					</div>
					
				</li>
				<li>
					<div class="fb-feature_box">
						<div class="fb-post" data-href="https://www.facebook.com/cuahangsangtao.official/posts/209272524718247" data-lazy="true" data-width="270" data-show-text="true"><blockquote cite="https://www.facebook.com/cuahangsangtao.official/posts/209272524718247" class="fb-xfbml-parse-ignore"><p>Máy Giặt Beko mã WY104764MW 10Kg, hàng chính hãng bảo hành 1 năm, máy vận hành êm ái, tiết kiệm điện giá khuyến mãi...</p>Người đăng: <a href="https://www.facebook.com/cuahangsangtao.official/">Cửa Hàng Sáng Tạo   dienlanhsangtao.com</a> vào&nbsp;<a href="https://www.facebook.com/cuahangsangtao.official/posts/209272524718247">Thứ Sáu, 3 tháng 12, 2021</a></blockquote></div>
						
					</div>
					
				</li>
				
			</ul>
		</div>
	<?php
	return ob_get_clean();

}

function facebook_feature_func( $atts ){
	
	return facebook_feature_template();
	
}

add_shortcode( 'facebook_feature', 'facebook_feature_func' );