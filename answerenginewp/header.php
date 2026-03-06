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
if ( is_page_template( 'page-scanner.php' ) ) {
    $nav_template = 'nav/nav-scanner';
}
get_template_part( 'template-parts/' . $nav_template );
?>
