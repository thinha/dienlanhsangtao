<?php
/**
 * The template for displaying the footer.
 *
 * @package flatsome
 */

global $flatsome_opt;
?>

</main>

<div id="someone-purchased" class="customized">
    <div>
        <img src="" class="someone__product-img">
        <p>
            Có khách hàng ở 
            <span class="location"></span>
             vừa đăng ký tư vấn sản phẩm
            <a href="" class="someone__product-title"></a>
            
            
            <small class="timeAgo"></small>
            
        </p>
    </div>
</div>

<div class="control-box">
    <div class="control-items">
        <button type="button" class="btn btn-register" data-toggle="modal" data-target="#customer_register">
            <span class="d-none d-sm-block mb-4">Tư Vấn Nhanh</span>
           <img src="<?= bloginfo('template_url'); ?>/assets/img/icons/booking.svg" alt="" class="icon">
           <span class="d-sm-none d-block">Tư Vấn Nhanh</span>
            <div class="circle-wave delay1"></div>
            <div class="circle-wave delay2"></div>
            <div class="circle-wave delay3"></div>
            <div class="circle-wave delay4"></div> 
        </button>
    </div>
</div>

<footer id="footer" class="footer-wrapper">

	<?php do_action('flatsome_footer'); ?>

</footer>

</div>

<script src="<?= bloginfo('template_url'); ?>/assets/js/jquery.toc.min.js"></script>
<?php wp_footer(); ?>
<script src="<?= bloginfo('template_url'); ?>/assets/js/scripts.js"></script>

</body>
</html>
