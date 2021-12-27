<?php
/**
 * Timber starter-theme
 * https://github.com/timber/starter-theme
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since   Timber 0.1
 */


if ( ! defined( '_S_VERSION' ) ) {
	// Replace the version number of the theme on each release.
	define( '_S_VERSION', '1.0.0' );
}

/**
 * If you are installing Timber as a Composer dependency in your theme, you'll need this block
 * to load your dependencies and initialize Timber. If you are using Timber via the WordPress.org
 * plug-in, you can safely delete this block.
 */
$composer_autoload = __DIR__ . '/vendor/autoload.php';
if ( file_exists( $composer_autoload ) ) {
	require_once $composer_autoload;
	$timber = new Timber\Timber();
}

/**
 * This ensures that Timber is loaded and available as a PHP class.
 * If not, it gives an error message to help direct developers on where to activate
 */
if ( ! class_exists( 'Timber' ) ) {

	add_action(
		'admin_notices',
		function() {
			echo '<div class="error"><p>Timber not activated. Make sure you activate the plugin in <a href="' . esc_url( admin_url( 'plugins.php#timber' ) ) . '">' . esc_url( admin_url( 'plugins.php' ) ) . '</a></p></div>';
		}
	);

	add_filter(
		'template_include',
		function( $template ) {
			return get_stylesheet_directory() . '/static/no-timber.html';
		}
	);
	return;
}

/**
 * Sets the directories (inside your theme) to find .twig files
 */
Timber::$dirname = array( 'templates', 'views' );

/**
 * By default, Timber does NOT autoescape values. Want to enable Twig's autoescape?
 * No prob! Just set this value to true
 */
Timber::$autoescape = false;


/**
 * We're going to configure our theme inside of a subclass of Timber\Site
 * You can move this to its own file and include here via php's include("MySite.php")
 */
class wfbt extends Timber\Site {
	/** Add timber support. */
	public function __construct() {
		add_action( 'after_setup_theme', array( $this, 'theme_supports' ) );
		add_action( 'widgets_init',  array( $this, 'wfbt_widgets_init' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'wfbt_scripts' ) );

		add_filter( 'timber_context',  array( $this, 'wfbt_add_to_context' ), 10, 1 );
		add_filter( 'get_twig',  array( $this, 'wfbt_add_to_twig'), 10, 1 );

		add_action('customize_register', array( $this, 'wfbt_customize_register'), 10, 1 );

		add_action( 'customize_register', array( $this, 'de_register'), 11 );
		parent::__construct();

	}

	function de_register( $wp_customize ) {
		// this is handled through add to context. 
		$wp_customize->remove_control('display_header_text');

		//This is being handled with the css/sass.
		$wp_customize->remove_section("colors");

		//Will handle this with the page templates using twig.
		$wp_customize->remove_section("header_image");
	}
	

	public function wfbt_add_to_context($context){
		// add sidebar availability here.
		$context['is_active_sidebar'] = is_active_sidebar( 'rightsidebar' );

		// Prepare Custom CSS.
		$context['addtional_css'] = $this->get_additional_css();
		$context['footer_text'] = $this->get_footer_text();
		$context['wp_debug'] = $this->get_debug_mode();

		if ( has_custom_logo() ) {
			// Custom logo may also be defined with a custom size. e.g.
			// $custom_logo = wp_get_attachment_image_src( get_theme_mod( 'custom_logo' ), 'custom_logo_size' );
			// **add_image_size( 'custom_logo_size', 400, 100, true );

			$custom_logo = wp_get_attachment_image_src( get_theme_mod( 'custom_logo' ), 'full' ); 

			$context['custom_logo'] = esc_url( $custom_logo[0] );
		}else{
			if( display_header_text() ){
				$context['bloginfo_name'] = get_bloginfo( 'name' );
				$context['bloginfo_description'] = get_bloginfo( 'description' );
			}
		}                         

		return $context;
	}

	private function get_footer_text(){
		$prepared_data = "";

		$prepared_data = get_theme_mod( 'wfbt_footer_text_setting' );

		return $prepared_data;
	}

	private function get_debug_mode(){
		$prepared_data = false;

		$debug_setting = get_theme_mod( 'wfbt_debug_setting' );

		if(WP_DEBUG || $debug_setting){
			$prepared_data = true;
		}

		return $prepared_data;
	}

	private function get_additional_css(){
		$prepared_data = "";
		$additional_css = wp_get_custom_css();

		if( 0 < strlen( $additional_css ) ){
			$prepared_data = '<style>'.$additional_css.'</style>';
		}

		return $prepared_data;
	}


	public function wfbt_customize_register( $wp_customize ) 
	{	
		$wp_customize->add_section('wfbt_general_section',array(
			'title'=>'General settings'
		));

		$wp_customize->add_setting('wfbt_debug_setting',array(
			'default'=>'1',
		));

		$wp_customize->add_control( 'wfbt_debug_control', array(
			'label'      => __( 'Enable debug mode', 'Kahuyan' ),
			'section'    => 'wfbt_general_section',
			'settings'   => 'wfbt_debug_setting',
			'type'       => 'checkbox',
			'std'        => '1'
		) );

		$wp_customize->add_section('wfbt_footer_section',array(
			'title'=>'Footer settings'
		));
			
		$wp_customize->add_setting('wfbt_footer_text_setting',array(
			'default'=>'Created by <a href="https://webfoundry.solutions" _target="blank" >Webfoundry.solutions</a>',
		));

		$wp_customize->add_control( 'wfbt_footer_text', array(
			'label'      => __( 'Footer text', 'Kahuyan' ),
			'section'=>'wfbt_footer_section',
			'settings'=>'wfbt_footer_text_setting',
			'type' => 'textarea',
		) );
		
	}

	public function wfbt_add_to_twig( $twig ) {
		// Not using this at this time.
		return $twig;
	  }

	public function wfbt_scripts() {
		wp_enqueue_style( 'wfbt-style', get_stylesheet_uri(), array(), _S_VERSION );
		wp_enqueue_style( 'wfbt-bootstrap-style', get_template_directory_uri() . '/bootstrap/css/main.min.css', array(), _S_VERSION );
		wp_enqueue_style( 'googlefont', 'https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400&&display=swap&display=swap',);
		
	
		wp_enqueue_script( 'wfbt-bootstrap-js', get_template_directory_uri() . '/bootstrap/js/bootstrap.bundle.min.js', array(), _S_VERSION, true );
	
		if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}
	}
	

	public function theme_supports() {
		// Add default posts and comments RSS feed links to head.
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'custom-header' );
		// custom size for logo. 
		//add_image_size( 'custom_logo_size', 400, 100, true );

		/*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
		add_theme_support(
			'html5',
			array(
				'search-form',
				'comment-form',
				'comment-list',
				'gallery',
				'caption',
				'style',
				'script',
			)
		);

		/*
		 * Enable support for Post Formats.
		 *
		 * See: https://codex.wordpress.org/Post_Formats
		 */
		add_theme_support(
			'post-formats',
			array(
				'aside',
				'image',
				'video',
				'quote',
				'link',
				'gallery',
				'audio',
			)
		);
	 
		add_theme_support( 'custom-logo', array(
			'height'               => 100,
			'width'                => 400,
			'flex-height'          => true,
			'flex-width'           => true,
			'header-text'          => array( 'site-title', 'site-description' ),
			'unlink-homepage-logo' => true, 
		) );

		register_nav_menus(
			array(
				'menu-1' => esc_html__( 'Primary', 'wfbt' ),
			)
		);

		add_theme_support( 'customize-selective-refresh-widgets' );
	}

	public function wfbt_widgets_init() {
		register_sidebar(
			array(
				'name'          => esc_html__( 'Right Sidebar', 'wfbt' ),
				'id'            => 'rightsidebar',
				'description'   => esc_html__( 'Add widgets here.', 'wfbt' ),
				'before_widget' => '<section id="%1$s" class="widget %2$s">',
				'after_widget'  => '</section>',
				'before_title'  => '<h2 class="widget-title">',
				'after_title'   => '</h2>',
			)
		);
	}

}

new wfbt();