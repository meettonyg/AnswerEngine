<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php
$nav_template = 'nav/nav-primary';
if ( is_page( 'scanner' ) || is_page_template( 'page-scanner.php' ) ) {
    $nav_template = 'nav/nav-scanner';
}
get_template_part( 'template-parts/' . $nav_template );
?>

<?php $is_scanner = is_page( 'scanner' ) || is_page_template( 'page-scanner.php' ); ?>
<?php if ( ! is_front_page() && ! $is_scanner ) : ?>
<script>
(function() {
  var nav = document.getElementById('siteNav');
  if (!nav) return;
  var scrolled = true;
  window.addEventListener('scroll', function() {
    var should = window.scrollY > 40;
    if (should !== scrolled) {
      scrolled = should;
      nav.classList.toggle('site-nav--scrolled', scrolled);
    }
  }, { passive: true });
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
