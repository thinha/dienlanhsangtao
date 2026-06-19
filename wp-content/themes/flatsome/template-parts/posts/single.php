<?php if ( have_posts() ) : ?>

<?php /* Start the Loop */ ?>

<?php while ( have_posts() ) : the_post(); ?>

<div class="entry-header-text entry-header-text-top text-<?php echo get_theme_mod( 'blog_posts_title_align', 'center' ); ?>">
	<?php get_template_part( 'template-parts/posts/partials/entry', 'title' ); ?>
</div>

<div class="appendix">
	<span class="down-ic"><i class="fas fa-list-ul"></i>Mục lục bài viết</span>
	<div class="item-gift">
		<ul id="toc" class="data-toc" data-toc="div.article-inner" data-toc-headings="h2,h3,h4"></ul>
	</div>
</div>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<div class="article-inner <?php flatsome_blog_article_classes(); ?>">
		<?php
			if(flatsome_option('blog_post_style') == 'default' || flatsome_option('blog_post_style') == 'inline'){
				get_template_part('template-parts/posts/partials/entry-header', flatsome_option('blog_posts_header_style') );
			}
		?>
		<?php get_template_part( 'template-parts/posts/content', 'single' ); ?>
	</div>
</article>

<?php endwhile; ?>

<?php else : ?>

	<?php get_template_part( 'no-results', 'index' ); ?>

<?php endif; ?>

<script>
$(document).on('click','.down-ic', function(){
	$(this).parent().toggleClass('check');
	$('.item-gift').slideToggle();
});	

</script>