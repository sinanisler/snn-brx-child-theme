<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Bricks\Element;
use Bricks\Frontend;

/**
 * Nestable utility wrapper that shows its children only when every
 * behavioural rule passes (show-once, x-times, cooldown, dates,
 * device, day-of-week, referrer, URL-contains, per-session …).
 * All CSS/JS is output inline.
 */
class SNN_Element_Conditions extends Element {

	/* === Element meta === */
	public $category     = 'snn';
	public $name         = 'conditions';
	public $label        = 'conditions';
	public $icon         = 'fas fa-eye';
	public $css_selector = '.snn-conditions';
	public $nestable     = true;       // can contain any children
	public $scripts      = [];         // unused – we embed JS inline

	public function get_label() {
		return esc_html__( 'Conditions (Nestable)', 'snn' );
	}

	/* === Builder controls === */
	public function set_controls() {

		// Show once
		$this->controls['show_once'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Show once', 'snn' ),
			'type'  => 'checkbox',
		];

		// Max views (persistent across sessions)
		$this->controls['max_views'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Max views (global)', 'snn' ),
			'type'    => 'number',
			'default' => 0,
		];

		// Views per session
		$this->controls['per_session'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Views per session', 'snn' ),
			'type'    => 'number',
			'default' => 0,
		];

		// Delay
		$this->controls['delay'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Delay (sec)', 'snn' ),
			'type'    => 'number',
			'default' => 0,
		];

		// Cool-down (also auto-hide duration)
		$this->controls['cooldown'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Cooldown (sec)', 'snn' ),
			'type'    => 'number',
			'default' => 0,
		];

		// Start date
		$this->controls['start_date'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'Start date', 'snn' ),
			'type'    => 'datepicker',
			'inline'  => true,
			// enforce ISO value → easier comparisons, human-friendly alt display
			'options' => [
				'enableTime' => true,
				'dateFormat' => 'Y-m-d H:i',
				'altInput'   => true,
				'altFormat'  => 'F j, Y h:i K',
				'time_24hr'  => true,
			],
		];

		// End date
		$this->controls['end_date'] = [
			'tab'     => 'content',
			'label'   => esc_html__( 'End date', 'snn' ),
			'type'    => 'datepicker',
			'inline'  => true,
			'options' => [
				'enableTime' => true,
				'dateFormat' => 'Y-m-d H:i',
				'altInput'   => true,
				'altFormat'  => 'F j, Y h:i K',
				'time_24hr'  => true,
			],
		];

		// Days of week
		$this->controls['days'] = [
			'tab'      => 'content',
			'label'    => esc_html__( 'Days of week', 'snn' ),
			'type'     => 'select',
			'multiple' => true,
			'options'  => [
				'sun' => __( 'Sunday', 'snn' ),
				'mon' => __( 'Monday', 'snn' ),
				'tue' => __( 'Tuesday', 'snn' ),
				'wed' => __( 'Wednesday', 'snn' ),
				'thu' => __( 'Thursday', 'snn' ),
				'fri' => __( 'Friday', 'snn' ),
				'sat' => __( 'Saturday', 'snn' ),
			],
			'inline'   => true,
		];

		// Dynamic condition key/value (server-side)
		$this->controls['condition_key'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Condition key =', 'snn' ),
			'type'  => 'text',
			'inline'=> true,
		];
		$this->controls['condition_value'] = [
			'tab'   => 'content',
			'label' => esc_html__( '= Condition value', 'snn' ),
			'type'  => 'text',
			'inline'=> true,
		];

		// Device / OS
		$this->controls['device'] = [
			'tab'        => 'content',
			'label'      => esc_html__( 'Device / OS', 'snn' ),
			'type'       => 'select',
			'multiple'   => true,
			'searchable' => true,
			'options'    => [
				''          => esc_html__( 'Any', 'snn' ),
				'desktop'   => esc_html__( 'Desktop', 'snn' ),
				'mobile'    => esc_html__( 'Mobile', 'snn' ),
				'windows'   => esc_html__( 'Windows', 'snn' ),
				'mac'       => esc_html__( 'macOS', 'snn' ),
				'ios'       => esc_html__( 'iOS', 'snn' ),
				'android'   => esc_html__( 'Android', 'snn' ),
			],
			'inline'     => true,
		];

		// Referrer contains
		$this->controls['referrer'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'Referrer contains (comma-separated)', 'snn' ),
			'type'  => 'text',
			'inline'=> true,
		];

		// URL contains
		$this->controls['url_contains'] = [
			'tab'   => 'content',
			'label' => esc_html__( 'URL contains (comma-separated)', 'snn' ),
			'type'  => 'text',
			'inline'=> true,
		];

		// User roles
		$this->controls['roles'] = [
			'tab'        => 'content',
			'label'      => esc_html__( 'User Roles', 'snn' ),
			'type'       => 'select',
			'multiple'   => true,
			'searchable' => true,
			'options'    => $this->wp_roles_select(),
			'inline'     => true,
		];
	}

	/* === Render === */
	public function render() {
		$settings = $this->settings;
		$instance = $this->id;
		$key      = 'cond_' . $instance;

		// 1) Dynamic condition check (server-side)
		if ( ! empty( $settings['condition_key'] ) && ! empty( $settings['condition_value'] ) ) {
			$left  = $this->render_dynamic_data( $settings['condition_key'] );
			$right = $this->render_dynamic_data( $settings['condition_value'] );
			if ( (string) $left !== (string) $right ) {
				return;
			}
		}

		// 2) Quick role gate
		if ( ! $this->role_allowed( $settings['roles'] ?? [] ) ) {
			return;
		}

		$this->set_attribute(
			'_root',
			'class',
			[ 'snn-conditions', 'snn-hidden' ] // start hidden
		);
		$this->set_attribute( '_root', 'data-key', $key );

		/* push every setting value to data-attributes */
		$map = [
			'show_once'     => 'bool',
			'max_views'     => 'int',
			'per_session'   => 'int',
			'delay'         => 'int',
			'cooldown'      => 'int',
			'start_date'    => 'str',
			'end_date'      => 'str',
			'days'          => 'str',
			'device'        => 'str',
			'referrer'      => 'str',
			'url_contains'  => 'str',
		];

		foreach ( $map as $field => $type ) {
			if ( isset( $settings[ $field ] ) && $settings[ $field ] !== '' ) {
				$val = $settings[ $field ];

				// ensure timestamp-friendly ISO fallback
				if ( in_array( $field, [ 'start_date', 'end_date' ], true ) ) {
					$val = preg_replace( '/\s+/', ' ', trim( $val ) ); // tidy spaces
				}

				if ( is_array( $val ) ) {
					$val = implode( ',', $val );
				}
				if ( $type === 'bool' ) {
					$val = $val ? '1' : '0';
				} elseif ( $type === 'int' ) {
					$val = intval( $val );
				} else {
					$val = (string) $val;
				}
				$this->set_attribute( '_root', 'data-' . $field, $val );
			}
		}

		/* === Output wrapper + children === */
		echo '<div ' . $this->render_attributes( '_root' ) . '>';
		echo Frontend::render_children( $this );
		echo '</div>';

		/* === Inline style (only first instance prints it) === */
		static $printed_inline = false;
		if ( ! $printed_inline ) {
			$printed_inline = true;
			echo '<style>.snn-hidden{display:none!important;}</style>';
		}

		/* === Inline script (guards against double-definition) === */
		static $printed_script = false;
		if ( ! $printed_script ) {
			$printed_script = true;
?>
<script>(function(){
if(window.SNNConditionsInit){return;} window.SNNConditionsInit=true;

/* safe storage helper */
function store(session){
	try{return session?sessionStorage:localStorage;}
	catch(e){return{getItem:()=>null,setItem:()=>{}};}
}
/* tolerant date-parser
 * accepts: ISO “2025-05-11 12:00”, flatpickr ISO, “May 11, 2025 12:00 PM”,
 *          or epoch-ms/epoch-s numbers.
 */
function dateParse(s){
	if(!s){return null;}
	if(/^\d+$/.test(s)){            // epoch seconds or ms
		const n=parseInt(s,10);
		return new Date(n>1e12?n:n*1000);
	}
	if(/^\d{4}-\d{2}-\d{2}\s/.test(s)){ // “YYYY-MM-DD HH:MM”
		return new Date(s.replace(' ','T'));
	}
	const d=new Date(s);            // let JS engine try
	return isNaN(d)?null:d;
}

document.addEventListener('DOMContentLoaded',function(){
	document.querySelectorAll('.snn-conditions').forEach(function(el){

		const o={
			key:          el.dataset.key,
			showOnce:     el.dataset.show_once==='1',
			maxViews:     parseInt(el.dataset.max_views||'0',10),
			delay:        parseInt(el.dataset.delay||'0',10)*1000,
			cooldown:     parseInt(el.dataset.cooldown||'0',10)*1000,
			sessionLimit: parseInt(el.dataset.per_session||'0',10),
			startDate:    dateParse(el.dataset.start_date),
			endDate:      dateParse(el.dataset.end_date),
			days:         el.dataset.days?el.dataset.days.split(','):[],
			device:       el.dataset.device?el.dataset.device.split(','):[],
			referrer:     el.dataset.referrer||'',
			urlContains:  el.dataset.url_contains||'',
		};

		const st   = store(o.sessionLimit>0);
		const now  = Date.now();
		const qs   = new URLSearchParams(location.search);
		let   rec  = JSON.parse(st.getItem(o.key)||'{}');

		/* debug helpers */
		if(qs.has('resetConditions')){st.setItem(o.key,'{}');rec={};}
		if(qs.has('forceShow')){render();return;}

		/* Date window */
		if(o.startDate && now<o.startDate.getTime()) return;
		if(o.endDate   && now>o.endDate.getTime())   return;

		/* Days of week */
		if(o.days.length){
			const dayNames=['sun','mon','tue','wed','thu','fri','sat'];
			const today   = dayNames[(new Date()).getDay()];
			if(!o.days.includes(today)) return;
		}

		/* Device / OS checks */
		if(o.device.length && !o.device.includes('') && !o.device.includes('any')){
			const ua=navigator.userAgent.toLowerCase();
			if(o.device.includes('desktop') && matchMedia('(max-width:767px)').matches) return;
			if(o.device.includes('mobile')  && matchMedia('(min-width:768px)').matches) return;
			if(o.device.includes('windows') && ua.indexOf('windows')===-1) return;
			if(o.device.includes('mac')     && ua.indexOf('macintosh')===-1 && ua.indexOf('mac os')===-1) return;
			if(o.device.includes('ios')     && !(/iphone|ipad|ipod/.test(ua))) return;
			if(o.device.includes('android') && ua.indexOf('android')===-1) return;
		}

		/* Referrer check */
		if(o.referrer){
			const allowed=o.referrer.split(',').map(s=>s.trim()).filter(Boolean);
			const ref=document.referrer||'';
			const hit=allowed.some(a=>ref.indexOf(a)!==-1);
			if(!hit) return;
		}

		/* URL contains check */
		if(o.urlContains){
			const parts=o.urlContains.split(',').map(s=>s.trim()).filter(Boolean);
			const href=location.href;
			const urlHit=parts.some(p=>href.indexOf(p)!==-1);
			if(!urlHit) return;
		}

		/* View / cooldown gates */
		const views=rec.v||0;
		if(o.showOnce     && views>=1)              return;
		if(o.maxViews     && views>=o.maxViews)     return;
		if(o.sessionLimit && views>=o.sessionLimit) return;
		if(o.cooldown     && rec.l && now-rec.l<o.cooldown) return;

		/* Delay, then render */
		if(o.delay){setTimeout(render,o.delay);}else{render();}

		function render(){
			el.classList.remove('snn-hidden');
			el.style.display='';
			st.setItem(o.key,JSON.stringify({v:views+1,l:Date.now()}));

			/* auto-hide after cooldown (supports Delay+Cooldown combo) */
			if(o.cooldown){
				setTimeout(function(){
					el.classList.add('snn-hidden');
					el.style.display='none';
				},o.cooldown);
			}
		}
	});
});
})();</script>
<?php
		}
	}

	/* === Helpers === */
	private function wp_roles_select(): array {
		global $wp_roles;
		if ( ! $wp_roles ) { $wp_roles = new WP_Roles(); }
		$out = [];
		foreach ( $wp_roles->roles as $id => $r ) {
			$out[ $id ] = translate_user_role( $r['name'] );
		}
		$out['guest'] = __( 'Guest', 'snn' );
		return $out;
	}

	private function role_allowed( $allowed ): bool {
		if ( empty( $allowed ) ) { return true; }
		$is_logged  = is_user_logged_in();
		$user_roles = $is_logged ? wp_get_current_user()->roles : [ 'guest' ];
		return (bool) array_intersect( $allowed, $user_roles );
	}

}
