<footer class="site-footer">
    <div class="container">
        <div class="site-footer__links">
            <a href="https://wordpress.org/plugins/answerenginewp/" class="site-footer__link" target="_blank" rel="noopener">WordPress Plugin Page</a>
            <a href="<?php echo esc_url( home_url( '/docs/' ) ); ?>" class="site-footer__link">Documentation</a>
            <a href="<?php echo esc_url( home_url( '/support/' ) ); ?>" class="site-footer__link">Support</a>
            <a href="<?php echo esc_url( home_url( '/badge/' ) ); ?>" class="site-footer__link">AI Visibility Badge</a>
            <a href="<?php echo esc_url( home_url( '/methodology/' ) ); ?>" class="site-footer__link">Methodology</a>
            <a href="<?php echo esc_url( home_url( '/privacy/' ) ); ?>" class="site-footer__link">Privacy</a>
        </div>
        <p class="site-footer__copyright">&copy; <?php echo esc_html( date( 'Y' ) ); ?> AnswerEngineWP</p>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
