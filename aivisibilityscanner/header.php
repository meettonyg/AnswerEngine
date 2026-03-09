<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php get_template_part( 'template-parts/nav/nav-scanner' ); ?>

<?php if ( ! is_front_page() ) : ?>
<script>
(function() {
  var nav = document.getElementById('siteNav');
  if (!nav) return;
  nav.classList.add('site-nav--scrolled');
})();
</script>
<?php endif; ?>

<script>
(function() {
  var toggle = document.getElementById('navToggle');
  var links = document.getElementById('navLinks');
  if (!toggle || !links) return;
  toggle.addEventListener('click', function() {
    var expanded = toggle.getAttribute('aria-expanded') === 'true';
    toggle.setAttribute('aria-expanded', String(!expanded));
    links.classList.toggle('is-open', !expanded);
  });
})();
</script>
