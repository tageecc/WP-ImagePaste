<?php
/*
Plugin Name: WP-ImagePaste
Plugin URI: 插件的介绍或更新地址
Description: 图片粘贴上传插件可以让你在WordPress编辑器里面直接粘贴上传文件
Version: 1.0
Author: 塔歌
Author URI: https://blog.tagee.cc
License: GPL2
*/

define('EXCEPT_PATH', dirname(__FILE__) . '/');

class ImagePaste {

	/*是否压缩图片*/
	private $compressed = false;
	/*图片压缩比例*/
	private $compress_num = 9;
	/*是否添加水印*/
	private $watermark = false;
	/*水印类型*/
	private $watermark_tpye = 'txt';
	/*水印文字*/
	private $watermark_txt = home_url();

	function __construct() {
		
		/* 注册激活插件时要调用的函数 */
		register_activation_hook(__FILE__, array($this, 'wp_imagepaste_activate'));
		/* 注册停用插件时要调用的函数 */
		register_deactivation_hook(__FILE__, array($this, 'wp_imagepaste_deactivate'));

		self::get_option();
		self::doAction();
		if (is_admin()) {
			/*  利用 admin_menu 钩子，添加菜单 */
			add_action('admin_menu', array($this, 'display_wp_imagepaste_menu'));
		}
	}
	function wp_imagepaste_activate() {

		$this->compressed = get_option('wp_imagepaste_option_compressed')||$this->compressed;
		$this->compress_num = get_option('wp_imagepaste_option_compress_num')||$this->compress_num;
	}
	function wp_imagepaste_deactivate() {}

	function get_option() {
		$this->compressed = get_option('wp_imagepaste_option_compressed');
		$this->compress_num = get_option('wp_imagepaste_option_compress_num');
	}
	function update_option($compressed,$compress_num)
	{
		update_option( 'wp_imagepaste_option_compressed', $compressed );
		update_option( 'wp_imagepaste_option_compress_num', $compress_num );
		$this->compressed =  $compressed;
		$this->compress_num = $compress_num;
	}
	function doAction() {
		/*添加js*/
		wp_enqueue_script('wp-imagepaste', plugins_url('js/index.js', __FILE__));
		/*注册路由*/
		wp_localize_script('wp-imagepaste', 'ajax_wp_imagepaste', array('uploadimage' => admin_url('admin-ajax.php')));
		/*路由逻辑*/
		add_action('wp_ajax_upload_image', array($this, 'upload_image'));
	}
	function upload_image() {
		$upload = wp_upload_dir();
		$uploadUrl = $upload['url'];
		$uploadDir = $upload['path'];
		$result = array('code' => 100);
		$file = (isset($_POST["file"])) ? $_POST["file"] : '';
		$extension = "png";
		if (!$file) {
			$result['code'] = - 1;
			$result['error'] = "Could not determine image extension type!";
		}
		if ($file) {
			$data = base64_decode(str_replace('data:image/' . $extension . ';base64,', '', $file));
			$name = md5($_POST['file']) . '.' . $extension; // 文件名md5处理
			$file = $uploadDir . '/' . $name;
			$fileUrl = $uploadUrl . '/' . $name;
			if (!file_put_contents($file, $data)) { //存储图片
				$result['code'] = - 2;
				$result['error'] = "Upload image error!";
			} else {
				$result['url'] = $fileUrl;
				/*是否压缩图片*/
				if($this->compressed){
					$image = @imagecreatefrompng($file);
					imagepng($image, $file, $this->compress_num);
				}
				
			}
			echo json_encode($result);
			wp_die();
		}
	}
	function display_wp_imagepaste_menu() {
		add_options_page('Set WP-ImagePaste', '图片粘贴上传插件', 'administrator', 'wp-imagepaste', array($this, 'display_wp_imagepaste_page'));
	}
	function display_wp_imagepaste_page() {

		if( !empty( $_POST ) && check_admin_referer( 'wp_imagepaste_option' ) ) {
		
			//更新设置
			self::update_option( $_POST['wp_imagepaste_option_compressed'],$_POST['wp_imagepaste_option_compress_num']  );
			?>
			<div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible" style="margin-left:0;">
				<p><strong>设置已保存。</strong></p>
				<button type="button" class="notice-dismiss"></button>
			</div>
			<?php
		}

		?>
		<form action="" method="post">
			<div>  
		        <h2>图片粘贴上传插件配置</h2>  
		        <hr>
		        <table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row" colspan="2"><h3>图片压缩</h3><hr></th>
						</tr>
						
						<tr valign="top">
							<th scope="row">
								<label for="compressed">自动压缩图片：</label>
							</th>
							
							<td>
								<input id="compressed" type="checkbox" name="wp_imagepaste_option_compressed" value="1" <?php checked( 1, $this->compressed ); ?> >
								<label for="compressed">开启</label>
								<p class="description">启用或禁用此功能</p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">
								<label for="wp_imagepaste_option_compress_num">压缩质量：</label>
							</th>
							<td>
								<input type="number" min="1" max="9" class="small-text code" id="wp_imagepaste_option_compress_num" name="wp_imagepaste_option_compress_num" value="<?php echo $this->compress_num ?>">
								<p class="description">压缩质量数值越大，压缩后文件越小，清晰度越差</p>
							<td>
						</tr>
						<tr valign="top">
							<th scope="row" colspan="2"><h3>图片水印</h3><hr></th>
						</tr>
						<tr valign="top">
							<th scope="row">
								<label for="watermark">自动添加水印：</label>
							</th>
							<td>
								<input id="watermark" type="checkbox" value="1" name="watermark">
								<label for="watermark">开启</label>
								<p class="description">启用或禁用此功能</p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">
								<label for="watermark_type">水印类型：</label>
							</th>
							<td>
								<select name="watermark_type" id="watermark_type">
									<option value="txt" selected="selected">文字</option>
								</select>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">
								<label for="watermark_txt">水印文字：</label>
							</th>
							<td>
								<input type="text" id="watermark_txt" name="watermark_txt" value="<?php echo $this->watermark_txt ?>">
							<td>
						</tr>
					</tbody>
				</table>
				<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="更新"></p>
				<?php
				//输出一个验证信息
				wp_nonce_field('wp_imagepaste_option');
				?>
		    </div>  
		</form>
		<?php
	}
}

$ImagePaste = new ImagePaste();
