<?php	/* rsBrainstage 3.3 */

class rsBrainstage extends rsCore {


	protected static $templates = array(
			0 => "Dashboard",
			1 => "Docs",
			2 => "Media",
			3 => "User",
			4 => "Email",
			5 => "Backup",
			6 => "Localization"
		);


	protected function detect_requested_page() {
		return self::$templates[ ( isset($_GET['i']) ? intval($_GET['i']) : 0 ) ];
	}


	protected function load_template() {
		$template = $this->detect_requested_page();
		define( 'TEMPLATE', $template );
		return new $template( $this->db, $this->head, $this->body );
	}


	protected function create_template( $name, $parent ) {
		if( !is_writable('../templates/') )
			die('Not enough permissions to write in ~/templates');

		if( $this->Benutzer->get_right('docs') != 1 )
			die('Only admin is allowed to do this.');

		$name = ucfirst($name);
		$success = true;
		// $Template -> Controller -> View -> Model -> $Parent
		$success = self::write_new_template_class_file( $name, $name .'_Controller', 'template' );
		$success = self::write_new_template_class_file( $name, $name .'_View', 'controller' );
		$success = self::write_new_template_class_file( $name, $name .'_Model', 'view' );
		$success = self::write_new_template_class_file( $name, $parent, 'model' );

		if( $success )
			die('ok');
		die();
	}


	private static function write_new_template_class_file( $name, $parent, $suffix="template" ) {
		$filepath = '../templates/'. $name .'.'. $suffix .'.php';
		if( file_exists( $filepath ) ) {
			echo 'File "'. $name .'.'. $suffix .'.php' .'" already exists.' ."\n";
			return false;
		}

		$content = '<'.'?'.'php	/* '. $name .' '. ucfirst($suffix) .' */

'. ($suffix !== 'template' ? 'abstract ' : '') .'class '. $name . ($suffix !== 'template' ? '_'. ucfirst($suffix) : '') .' extends '. $parent .' {

}';

		$file = fopen( $filepath, 'w' );
		fwrite( $file, $content );
		fclose( $file );

		return true;
	}


}
