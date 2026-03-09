<?php
/**
 * Leaderboard Page Template
 *
 * Displays ranked scan results as a public leaderboard.
 * Loaded via rewrite rule: /leaderboard/
 *
 * @package AIVisibilityScanner
 */

$segment = isset( $_GET['segment'] ) ? sanitize_text_field( $_GET['segment'] ) : '';
$limit   = isset( $_GET['limit'] ) ? min( 100, max( 1, intval( $_GET['limit'] ) ) ) : 50;

$entries = aivs_get_leaderboard( $segment, $limit );

$title = $segment
	? 'Top ' . count( $entries ) . ' ' . esc_html( ucfirst( $segment ) ) . ' Sites by AI Visibility'
	: 'Top ' . count( $entries ) . ' Sites by AI Visibility';

// Custom head for OG tags.
add_action( 'wp_head', function() use ( $title ) {
	echo '<meta property="og:title" content="' . esc_attr( $title ) . ' &middot; AI Visibility Scanner">' . "\n";
	echo '<meta property="og:description" content="See which sites score highest on the AI Visibility Score. Rankings based on structural analysis of AI extractability signals.">' . "\n";
	echo '<meta name="description" content="See which sites score highest on the AI Visibility Score. Rankings based on structural analysis of AI extractability signals.">' . "\n";
} );

get_header();
?>

<main>
<section class="leaderboard-page">
	<div class="container">
		<div class="leaderboard-page__header">
			<h1 class="leaderboard-page__title"><?php echo esc_html( $title ); ?></h1>
			<p class="leaderboard-page__date">Updated <?php echo esc_html( date( 'F j, Y' ) ); ?></p>
		</div>

		<?php if ( ! empty( $entries ) ) : ?>
		<div class="leaderboard-table">
			<div class="leaderboard-table__header">
				<span class="leaderboard-table__col leaderboard-table__col--rank">Rank</span>
				<span class="leaderboard-table__col leaderboard-table__col--domain">Domain</span>
				<span class="leaderboard-table__col leaderboard-table__col--score">Score</span>
				<span class="leaderboard-table__col leaderboard-table__col--tier">Tier</span>
			</div>

			<?php foreach ( $entries as $entry ) :
				$is_top_3 = $entry['rank'] <= 3;
				$row_class = 'leaderboard-row';
				if ( $is_top_3 ) {
					$row_class .= ' leaderboard-row--top-3';
				}
			?>
			<div class="<?php echo esc_attr( $row_class ); ?>">
				<span class="leaderboard-row__rank"><?php echo intval( $entry['rank'] ); ?></span>
				<span class="leaderboard-row__domain"><a href="<?php echo esc_url( home_url( '/report/' . $entry['domain'] ) ); ?>"><?php echo esc_html( $entry['domain'] ); ?></a></span>
				<span class="leaderboard-row__score" style="color:<?php echo esc_attr( $entry['tier_color'] ); ?>"><?php echo intval( $entry['score'] ); ?></span>
				<span class="leaderboard-row__tier">
					<span class="leaderboard-tier-chip" style="color:<?php echo esc_attr( $entry['tier_color'] ); ?>;border-color:<?php echo esc_attr( $entry['tier_color'] ); ?>">
						<?php echo esc_html( $entry['tier_label'] ); ?>
					</span>
				</span>
			</div>
			<?php endforeach; ?>
		</div>
		<?php else : ?>
		<div class="leaderboard-page__empty">
			<p>No scan results yet. Be the first!</p>
		</div>
		<?php endif; ?>

		<!-- CTA -->
		<div class="leaderboard-page__cta">
			<a href="<?php echo esc_url( home_url( '/scan/' ) ); ?>" class="btn btn--primary">
				Run your own scan &rarr;
			</a>
		</div>

		<!-- Methodology note -->
		<div class="leaderboard-page__methodology">
			<p>Rankings based on AI Visibility Scanner structural AI visibility analysis. Scores reflect extractability signals, not endorsement or traffic.</p>
		</div>
	</div>
</section>
</main>

<?php get_footer(); ?>
