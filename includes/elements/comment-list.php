<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Bricks\Element;

/**
 * Comment List element (inline edit / delete ready)
 */
class SNN_Element_Comment_List extends Element {
	/* --------------------------------------------------------------------- */
	public $category     = 'snn';
	public $name         = 'comment-list';
	public $icon         = 'ti-comments';
	public $css_selector = '.snn-commentlist';
	public $nestable     = false;
	/* --------------------------------------------------------------------- */
	public function get_label() { return esc_html__( 'Comment List', 'bricks' ); }

	/* ---------- CONTROL GROUPS ------------------------------------------ */
	public function set_control_groups() {
		$this->control_groups['settings'] = [
			'title' => esc_html__( 'Settings', 'bricks' ),
			'tab'   => 'content',
		];
		$this->control_groups['style'] = [
			'title' => esc_html__( 'Style', 'bricks' ),
			'tab'   => 'style',
		];
	}

	/* ---------- CONTROLS ------------------------------------------------- */
	public function set_controls() {

		$this->controls['avatar'] = [
			'tab'     => 'content',
			'group'   => 'settings',
			'label'   => esc_html__( 'Avatar size', 'bricks' ),
			'type'    => 'number',
			'unit'    => 'px',
			'min'     => 16,
			'max'     => 128,
			'default' => 48,
		];

		$this->controls['order'] = [
			'tab'     => 'content',
			'group'   => 'settings',
			'label'   => esc_html__( 'Order', 'bricks' ),
			'type'    => 'select',
			'options' => [ 'ASC' => 'ASC', 'DESC' => 'DESC' ],
			'default' => 'ASC',
			'inline'  => true,
		];

		$this->controls['inline_edit'] = [
			'tab'     => 'content',
			'group'   => 'settings',
			'label'   => esc_html__( 'Enable inline edit', 'bricks' ),
			'type'    => 'checkbox',
			'default' => true,
			'inline'  => true,
		];

		/* example style-control */
		$this->controls['typography'] = [
			'tab'   => 'style',
			'group' => 'style',
			'label' => esc_html__( 'Comment typography', 'bricks' ),
			'type'  => 'typography',
			'css'   => [
				[
					'property' => 'typography',
					'selector' => '.snn-comment-content',
				],
			],
		];
	}

	/* ---------- RENDER --------------------------------------------------- */
	public function render() {

		$avatar  = intval( $this->settings['avatar'] ?? 48 );
		$order   = $this->settings['order'] ?? 'ASC';
		$enable  = ! empty( $this->settings['inline_edit'] );

		$this->set_attribute( '_root', 'class', 'snn-comment-list-wrapper' );
		echo '<div ' . $this->render_attributes( '_root' ) . '>';

		/* === CSS (only list / edit buttons) ============================ */
		?>
<style>
#comment{display:none!important}
.snn-commentlist{list-style:none;margin:0;padding:0}
.snn-comment-item{display:flex;align-items:flex-start;padding:25px 0;position:relative}
.snn-comment-author{flex:0 0 120px;text-align:center;padding-right:15px}
.snn-comment-author-avatar-link img{width:<?php echo esc_attr( $avatar ); ?>px;height:<?php echo esc_attr( $avatar ); ?>px;border-radius:5px;display:block;margin:0 auto}
.snn-comment-author-link{display:block;margin-top:8px;font-weight:bold;color:#333;text-decoration:none}
.snn-comment-metadata{font-size:12px;color:#999;margin-top:4px}
.snn-comment-body{display:flex;gap:10px;width:100%}
.snn-comment-content{background:#f9f9f9;padding:12px;border-radius:6px;line-height:1.5;width:100%}
.snn-comment-reply{margin-top:8px;font-size:13px}
.snn-comment-reply a{text-decoration:none;color:#0073aa}
.snn-comment-reply a:hover{color:#005177}
<?php if ( $enable ) : ?>
.snn-comment-edit-btn,.snn-comment-delete-btn,.snn-comment-save-btn,.snn-comment-cancel-btn{position:absolute;bottom:10px;right:0px;padding:3px 6px;font-size:11px;background:#eee;border:1px solid #ccc;border-radius:4px;cursor:pointer;line-height:1;display:none}
.snn-comment-delete-btn{right:55px}.snn-comment-save-btn{right:10px}.snn-comment-cancel-btn{right:55px}
.snn-comment-item:hover .snn-comment-edit-btn,.snn-comment-item:hover .snn-comment-delete-btn{display:block}
.snn-comment-item.editing .snn-comment-save-btn,.snn-comment-item.editing .snn-comment-cancel-btn{display:block}
.snn-comment-item.editing .snn-comment-edit-btn,.snn-comment-item.editing .snn-comment-delete-btn{display:none}
img.snn-img-align-left{float:left;margin-right:10px;margin-bottom:10px}
img.snn-img-align-right{float:right;margin-left:10px;margin-bottom:10px}
img.snn-img-align-center{display:block;float:none;margin:auto;margin-bottom:10px}
img.snn-img-align-none{display:block;float:none;margin:0 0 10px}
img.snn-selected-image{outline:2px solid #0073aa;outline-offset:2px}

<?php endif; ?>
</style>
		<?php
		/* === LIST ====================================================== */
		$comments = get_comments( [
			'post_id' => get_the_ID(),
			'status'  => 'approve',
			'order'   => $order,
		] );

		if ( ! function_exists( 'snn_comment_callback' ) ) {
			function snn_comment_callback( $comment, $args, $depth ) {
				$GLOBALS['comment'] = $comment;
				$avatar_size = $args['avatar_size'];
				?>
<li <?php comment_class( 'snn-comment-item' ); ?> id="comment-<?php comment_ID(); ?>">
	<comment id="div-comment-<?php comment_ID(); ?>" class="snn-comment-body">
		<div class="snn-comment-author snn-comment-vcard">
			<?php
			if ( $comment->user_id ) {
				$u = get_userdata( $comment->user_id );
				if ( $u ) {
					$profile = home_url( '/author/' . $u->user_login );
					echo '<a href="' . esc_url( $profile ) . '" class="snn-comment-author-avatar-link">' . get_avatar( $comment, $avatar_size ) . '</a>';
					printf( '<a href="%s" class="snn-comment-author-link">%s</a>', esc_url( $profile ), esc_html( $u->display_name ) );
				}
			} else {
				echo get_avatar( $comment, $avatar_size );
				comment_author_link();
			}
			?>
			<div class="snn-comment-metadata">
				<a href="<?php echo esc_url( get_comment_link( $comment->comment_ID ) ); ?>">
					<time datetime="<?php comment_time( 'c' ); ?>"><?php printf( '%1$s at %2$s', get_comment_date(), get_comment_time() ); ?></time>
				</a>
			</div>
		</div>
		<div class="snn-comment-content">
			<?php
				/* -------- RAW OUTPUT -------- */   // CHANGED
				echo $comment->comment_content;
			?>

            
		</div>


        </comment>
</li>
				<?php
			}
		}
		echo '<ul class="snn-commentlist">';
		wp_list_comments( [
			'style'       => 'ul',
			'callback'    => 'snn_comment_callback',
			'avatar_size' => $avatar,
			'max_depth'   => 4,
		], $comments );
		echo '</ul>';

		/* === INLINE JS (edit / delete) ================================= */
		if ( $enable ) {
			$current  = get_current_user_id();
			$is_admin = current_user_can( 'edit_others_comments' );
			$editable = $deletable = [];

			foreach ( $comments as $c ) {
				$age_ok = ( current_time( 'timestamp', true ) - strtotime( $c->comment_date_gmt ) ) <= 30 * DAY_IN_SECONDS;
				if ( $is_admin ) {
					$editable[]  = $c->comment_ID;
					$deletable[] = $c->comment_ID;
				} elseif ( (int) $c->user_id === $current && $age_ok ) {
					$editable[] = $c->comment_ID;
				}
			}
?>
<script>
document.addEventListener('DOMContentLoaded',()=>{

	const ajaxurl   = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
	      nonce     = '<?php echo wp_create_nonce( 'snn_comment_edit_nonce' ); ?>',
	      editable  = <?php echo wp_json_encode( $editable ); ?>,
	      deletable = <?php echo wp_json_encode( $deletable ); ?>;

	const postJSON = (action, payload) => {
		const fd = new FormData();
		fd.append('action', action);
		fd.append('_ajax_nonce', nonce);
		for (const k in payload) fd.append(k, payload[k]);
		return fetch(ajaxurl, { method:'POST', credentials:'same-origin', body:fd }).then(r => r.json());
	};

	function beginEdit(li, id){
		const ct = li.querySelector('.snn-comment-content');
		if (!ct || ct.dataset.editing === '1') return;

		const original = ct.innerHTML;
		ct.dataset.editing = '1';
		ct.contentEditable = 'true';          /* <-- rich text */  // CHANGED
		ct.focus();

		li.classList.add('editing');
		const save   = Object.assign(document.createElement('button'), { className:'snn-comment-save-btn',   textContent:'✔ Save'   }),
		      cancel = Object.assign(document.createElement('button'), { className:'snn-comment-cancel-btn', textContent:'✖ Cancel' });

		li.append(save, cancel);

		const finish = () => {
			ct.removeAttribute('contenteditable');
			ct.dataset.editing = '0';
			li.classList.remove('editing');
			save.remove(); cancel.remove();
		};

		cancel.onclick = () => { ct.innerHTML = original; finish(); };

		save.onclick = () => {
			const html = ct.innerHTML.trim();
			if (!html.length){ alert('Comment cannot be empty'); return; }

			save.disabled = true;
			postJSON('snn_comment_edit', {
				comment_id: id,
				content: html                        // CHANGED
			})
			.then(j => {
				if (j?.success && j.data?.content){
					ct.innerHTML = j.data.content;
					finish();
				}else{
					alert(j?.data || 'Error'); save.disabled = false;
				}
			})
			.catch(() => { alert('Network'); save.disabled = false; });
		};
	}

	function deleteComment(li, id){
		if (!confirm('Delete this comment permanently?')) return;
		postJSON('snn_comment_delete',{ comment_id:id })
		.then(j => j?.success ? li.remove() : alert(j?.data || 'Failed'))
		.catch(() => alert('Network'));
	}

	function addButtons(){
		editable.forEach(id => {
			const li = document.getElementById(`comment-${id}`); if (!li) return;
			const b  = Object.assign(document.createElement('button'), { className:'snn-comment-edit-btn', textContent:'Edit' });
			b.onclick = () => beginEdit(li, id);
			li.appendChild(b);
		});
		deletable.forEach(id => {
			const li = document.getElementById(`comment-${id}`); if (!li) return;
			const b  = Object.assign(document.createElement('button'), { className:'snn-comment-delete-btn', textContent:'Del' });
			b.onclick = () => deleteComment(li, id);
			li.appendChild(b);
		});
	}
	addButtons();
});
</script>
<?php
		} // end if enable
		echo '</div>';
	}
}

/* -------------------------------------------------------------------------
 * AJAX handlers – keep them in the same file or move to a separate plugin file
 * ---------------------------------------------------------------------- */

/* EDIT ------------------------------------------------------------------ */
add_action( 'wp_ajax_snn_comment_edit', 'snn_comment_edit_ajax' );
function snn_comment_edit_ajax() {

	check_ajax_referer( 'snn_comment_edit_nonce', '_ajax_nonce' );

	$comment_id = isset( $_POST['comment_id'] ) ? (int) $_POST['comment_id'] : 0;
	$content    = isset( $_POST['content'] ) ? wp_unslash( $_POST['content'] ) : ''; // CHANGED

	if ( ! $comment_id || '' === $content ) {
		wp_send_json_error( 'Invalid data', 400 );
	}

	if ( ! current_user_can( 'edit_comment', $comment_id ) ) {
		wp_send_json_error( 'No permission', 403 );
	}

	/* ---- disable KSES completely while we update ---- */  // CHANGED
	if ( function_exists( 'kses_remove_filters' ) ) kses_remove_filters();

	$updated = wp_update_comment( [
		'comment_ID'      => $comment_id,
		'comment_content' => $content,
	] );

	if ( function_exists( 'kses_init_filters' ) ) kses_init_filters();
	/* -------------------------------------------------- */

	if ( ! $updated ) {
		wp_send_json_error( 'Update failed', 400 );
	}

	$comment = get_comment( $comment_id );
	wp_send_json_success( [
		'content' => $comment->comment_content,             // raw back to editor
	] );
}

/* DELETE ---------------------------------------------------------------- */
add_action( 'wp_ajax_snn_comment_delete', 'snn_comment_delete_ajax' );
function snn_comment_delete_ajax() {

	check_ajax_referer( 'snn_comment_edit_nonce', '_ajax_nonce' );

	$comment_id = isset( $_POST['comment_id'] ) ? (int) $_POST['comment_id'] : 0;

	if ( ! $comment_id ) {
		wp_send_json_error( 'Invalid data', 400 );
	}

	if ( ! current_user_can( 'edit_comment', $comment_id ) ) {
		wp_send_json_error( 'No permission', 403 );
	}

	if ( ! wp_delete_comment( $comment_id, true ) ) {
		wp_send_json_error( 'Delete failed', 400 );
	}

	wp_send_json_success();
}
