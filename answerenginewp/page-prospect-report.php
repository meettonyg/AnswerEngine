<?php
/**
 * Prospect Report Page
 *
 * Agency-branded audit report for prospects. Same scan data as /score/{hash}
 * but with agency branding and prospect-focused framing.
 *
 * Loaded via rewrite rule: /prospect-report/{hash}
 * Not indexed by search engines (noindex, nofollow).
 *
 * @package AnswerEngineWP
 */

$hash = get_query_var( 'aewp_prospect_hash' );
$scan = aewp_get_scan_by_hash( $hash );

if ( ! $scan ) {
	status_header( 404 );
	get_header();
	?>
	<main>
	<div class="page-404">
		<div class="container">
			<div class="page-404__code">404</div>
			<p class="page-404__message">This audit report was not found.</p>
			<a href="<?php echo esc_url( home_url( '/scanner/?mode=prospect' ) ); ?>" class="btn btn--primary">Run an audit &rarr;</a>
		</div>
	</div>
	</main>
	<?php
	get_footer();
	return;
}

$url           = get_post_meta( $scan->ID, '_aewp_url', true );
$score         = intval( get_post_meta( $scan->ID, '_aewp_score', true ) );
$sub_scores    = get_post_meta( $scan->ID, '_aewp_sub_scores', true );
$extraction    = get_post_meta( $scan->ID, '_aewp_extraction_data', true );
$fixes         = get_post_meta( $scan->ID, '_aewp_fixes', true );
$scanned_at    = get_post_meta( $scan->ID, '_aewp_scanned_at', true );
$competitor    = get_post_meta( $scan->ID, '_aewp_competitor_data', true );
$tier          = aewp_get_tier( $score );
$gauge_offset  = 283 - ( 283 * $score / 100 );
$domain        = aewp_format_domain( $url );

// Agency branding
$agency_name   = aewp_get_agency_name();
$agency_logo   = aewp_get_agency_logo_url();
$agency_url    = aewp_get_agency_url();
$agency_email  = aewp_get_agency_email();
$has_agency    = aewp_has_agency_branding();

// noindex + meta
add_action( 'wp_head', function() use ( $url, $score, $tier, $domain ) {
	echo '<meta name="robots" content="noindex, nofollow">' . "\n";
	echo '<meta property="og:title" content="AI Visibility Audit: ' . esc_attr( $domain ) . ' — ' . $score . '/100">' . "\n";
	echo '<meta property="og:description" content="' . esc_attr( $domain ) . ' scored ' . $score . '/100 (' . $tier['label'] . ') on the AI Visibility Score.">' . "\n";
} );

get_header();
?>

<main>
<section class="prospect-report">
	<div class="container">

		<!-- Agency Header -->
		<div class="prospect-report__agency-header">
			<?php if ( $has_agency ) : ?>
				<div class="prospect-report__agency-brand">
					<?php if ( $agency_logo ) : ?>
						<img src="<?php echo esc_url( $agency_logo ); ?>" alt="<?php echo esc_attr( $agency_name ); ?>" class="prospect-report__agency-logo">
					<?php endif; ?>
					<div class="prospect-report__agency-meta">
						<span class="prospect-report__prepared-by">Prepared by <?php echo esc_html( $agency_name ); ?></span>
						<?php if ( $agency_url ) : ?>
							<span class="prospect-report__agency-url"><?php echo esc_html( preg_replace( '#^https?://#', '', rtrim( $agency_url, '/' ) ) ); ?></span>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>
			<div class="prospect-report__audit-title">
				<h1 class="prospect-report__h1">AI Visibility Audit</h1>
				<p class="prospect-report__for">for <?php echo esc_html( $domain ); ?></p>
				<?php if ( $scanned_at ) : ?>
					<p class="prospect-report__date"><?php echo esc_html( date( 'F j, Y', strtotime( $scanned_at ) ) ); ?></p>
				<?php endif; ?>
			</div>
		</div>

		<div class="scanner-results">
			<!-- Score Hero -->
			<div class="scanner-results__hero">
				<div class="score-gauge">
					<svg viewBox="0 0 100 100">
						<circle class="score-gauge__track" cx="50" cy="50" r="45" stroke-width="8"/>
						<circle class="score-gauge__fill" cx="50" cy="50" r="45"
								stroke="<?php echo esc_attr( $tier['color'] ); ?>"
								stroke-dasharray="283"
								stroke-dashoffset="<?php echo esc_attr( $gauge_offset ); ?>"
								transform="rotate(-90 50 50)"/>
						<text class="score-gauge__value" x="50" y="45"><?php echo esc_html( $score ); ?></text>
						<text class="score-gauge__max" x="50" y="68">/100</text>
					</svg>
				</div>
				<div class="scanner-results__tier">
					<span class="pill pill--tier pill--<?php echo esc_attr( $tier['class'] ); ?>"><?php echo esc_html( $tier['label'] ); ?></span>
				</div>
				<p class="scanner-results__tier-message"><?php echo esc_html( $tier['message'] ); ?></p>
			</div>

			<!-- Sub-scores -->
			<?php if ( is_array( $sub_scores ) && ! empty( $sub_scores ) ) : ?>
			<div class="sub-scores">
				<h2 class="sub-scores__title">Score Breakdown</h2>
				<?php foreach ( $sub_scores as $key => $sub ) :
					if ( ! is_array( $sub ) ) continue;
					$sub_tier = aewp_get_tier( $sub['score'] );
				?>
					<div class="sub-score">
						<div class="sub-score__header">
							<span class="sub-score__label"><?php echo esc_html( $sub['label'] ); ?></span>
							<span class="sub-score__value" style="color:<?php echo esc_attr( $sub_tier['color'] ); ?>"><?php echo intval( $sub['score'] ); ?>/100</span>
						</div>
						<div class="sub-score__bar">
							<div class="sub-score__fill" style="width:<?php echo intval( $sub['score'] ); ?>%;background:<?php echo esc_attr( $sub_tier['color'] ); ?>"></div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>

			<!-- Extraction Preview -->
			<?php if ( is_array( $extraction ) ) : ?>
			<div class="extraction">
				<h2 class="extraction__title">What AI Can Extract</h2>
				<div class="extraction__grid">
					<div>
						<div class="extraction__column-title extraction__column-title--found">Found</div>
						<?php if ( ! empty( $extraction['schema_types'] ) ) : ?>
							<?php foreach ( $extraction['schema_types'] as $type ) : ?>
								<div class="extraction__item extraction__item--found">&#10003; <?php echo esc_html( $type ); ?> schema</div>
							<?php endforeach; ?>
						<?php endif; ?>
						<?php if ( ! empty( $extraction['entities'] ) ) : ?>
							<?php foreach ( array_slice( $extraction['entities'], 0, 5 ) as $entity ) : ?>
								<div class="extraction__item extraction__item--found">&#10003; Entity: <?php echo esc_html( $entity ); ?></div>
							<?php endforeach; ?>
						<?php endif; ?>
						<?php if ( ! empty( $extraction['headlines'] ) ) : ?>
							<?php foreach ( array_slice( $extraction['headlines'], 0, 5 ) as $headline ) : ?>
								<div class="extraction__item extraction__item--found">&#10003; <?php echo esc_html( $headline ); ?></div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
					<div>
						<div class="extraction__column-title extraction__column-title--missing">Missing</div>
						<?php if ( ! empty( $extraction['missing'] ) ) : ?>
							<?php foreach ( $extraction['missing'] as $item ) : ?>
								<div class="extraction__item extraction__item--missing">&#10007; <?php echo esc_html( $item ); ?></div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<!-- Competitor Comparison -->
			<?php if ( is_array( $competitor ) && ! empty( $competitor ) ) : ?>
			<div class="competitor-gap">
				<h2 class="competitor-gap__title">How <?php echo esc_html( $domain ); ?> compares to <?php echo isset( $competitor['url'] ) ? esc_html( $competitor['url'] ) : 'competitor'; ?></h2>
				<?php
				echo aewp_render_comparison_bars(
					array( 'score' => $score, 'sub_scores' => $sub_scores ),
					$competitor,
					esc_html( $domain ),
					isset( $competitor['url'] ) ? $competitor['url'] : 'Competitor'
				);
				?>
			</div>
			<?php endif; ?>

			<!-- Top 3 Fixes -->
			<?php if ( is_array( $fixes ) && ! empty( $fixes ) ) : ?>
			<div class="fixes">
				<h2 class="fixes__title">Recommended Fixes</h2>
				<p class="fixes__subtitle">Top 3 actions to improve AI visibility</p>
				<?php foreach ( $fixes as $fix ) :
					if ( ! is_array( $fix ) ) continue;
				?>
					<div class="fix-card">
						<div class="fix-card__header">
							<span class="fix-card__title"><?php echo esc_html( $fix['title'] ); ?></span>
							<span class="fix-card__points">+<?php echo intval( $fix['points'] ); ?> pts</span>
						</div>
						<p class="fix-card__desc"><?php echo esc_html( $fix['description'] ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>

			<!-- CTAs -->
			<div class="scanner-results__ctas">
				<div class="scanner-results__cta-primary">
					<a href="https://wordpress.org/plugins/answerenginewp/" class="btn btn--primary" target="_blank" rel="noopener">
						Fix your AI visibility &rarr; Install AnswerEngineWP (Free)
					</a>
				</div>
				<div class="scanner-results__cta-actions">
					<a href="<?php echo esc_url( rest_url( 'aewp/v1/prospect-report/' . $hash ) ); ?>" class="btn btn--outline" target="_blank" rel="noopener">Download Audit PDF</a>
					<a href="<?php echo esc_url( home_url( '/scanner/?mode=prospect' ) ); ?>" class="btn btn--outline">Run another audit</a>
				</div>
			</div>
		</div>

		<!-- Footer attribution -->
		<div class="prospect-report__footer">
			<p>Technology powered by <a href="<?php echo esc_url( home_url( '/scanner/' ) ); ?>">AI Visibility Scanner</a> &middot; answerenginewp.com</p>
		</div>

	</div>
</section>
</main>

<style>
/* Prospect Report — Agency-branded audit page */
.prospect-report {
	padding: 48px 0 80px;
}
.prospect-report__agency-header {
	margin-bottom: 48px;
	padding-bottom: 32px;
	border-bottom: 1px solid var(--gray-200);
}
.prospect-report__agency-brand {
	display: flex;
	align-items: center;
	gap: 16px;
	margin-bottom: 24px;
}
.prospect-report__agency-logo {
	max-height: 48px;
	width: auto;
}
.prospect-report__agency-meta {
	display: flex;
	flex-direction: column;
}
.prospect-report__prepared-by {
	font-size: 0.95rem;
	font-weight: 500;
	color: var(--gray-700);
}
.prospect-report__agency-url {
	font-size: 0.85rem;
	color: var(--gray-400);
}
.prospect-report__h1 {
	font-family: 'Instrument Serif', Georgia, serif;
	font-size: 2.2rem;
	font-weight: 400;
	color: var(--gray-900);
	margin-bottom: 4px;
}
.prospect-report__for {
	font-size: 1.2rem;
	color: var(--blue);
	font-weight: 500;
}
.prospect-report__date {
	font-size: 0.9rem;
	color: var(--gray-400);
	margin-top: 4px;
}
.prospect-report__footer {
	margin-top: 64px;
	padding-top: 24px;
	border-top: 1px solid var(--gray-200);
	text-align: center;
	font-size: 0.85rem;
	color: var(--gray-400);
}
.prospect-report__footer a {
	color: var(--gray-500);
}
</style>

<?php get_footer(); ?>
