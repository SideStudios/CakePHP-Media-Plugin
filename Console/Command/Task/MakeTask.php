<?php
/**
 * Make Task File
 *
 * Copyright (c) 2007-2012 David Persson
 *
 * Distributed under the terms of the MIT License.
 * Redistributions of files must retain the above copyright notice.
 *
 * PHP version 5
 * CakePHP version 1.3
 *
 * @package    media
 * @subpackage media.shells.tasks
 * @copyright  2007-2012 David Persson <davidpersson@gmx.de>
 * @license    http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link       http://github.com/davidpersson/media
 */

/**
 * Make Task Class
 *
 * @package    media
 * @subpackage media.shells.tasks
 */
class MakeTask extends MediaShell {

	var $source;

	var $model;

	var $_Model;

/**
 * Main execution methpd
 *
 * @access public
 * @return void
 */
	function execute() {
		if (isset($this->params['model'])) {
			$this->model = $this->params['model'];
		 } else {
			$this->model = $this->in('Model', null, 'Media.Attachment');
		}
		$this->_Model = ClassRegistry::init($this->model);

		if (!isset($this->_Model->Behaviors->Generator)) {
			$this->error("Model `{$this->model}` has the `Generator` behavior not attached to it.");
		}
		$settings = $this->_Model->Behaviors->Generator->settings[$this->_Model->alias];

		if (!$this->source = array_shift($this->args)) {
			$this->source = $this->in('Source directory', null, $settings['baseDirectory']);
		}
		$message = 'Regex (matches against the basenames of the files) for source inclusion:';
		$pattern = $this->in($message, null, '.*');

		$image_versions = Configure::read('Media.filter.image');
		$message = 'Image Versions? (All,' . implode(', ', array_keys($image_versions)) . ')';
		$version = $this->in($message, null, 'All');

		if ($version != 'All') {
			Configure::write('Media.filter.image', array($version => $image_versions[$version]));
		}

		$message = 'Overwrite? (yes,no)';
		$overwrite = $this->in($message, null, $settings['overwrite'] ? 'yes' : 'no');
		$settings['overwrite'] = $overwrite == 'yes' ? true : false;
		$this->_Model->Behaviors->Generator->settings[$this->_Model->alias]['overwrite'] = $settings['overwrite'];

		$this->out();
		$this->out(sprintf('%-25s: %s', 'Base', $this->shortPath($settings['baseDirectory'])));
		$this->out(sprintf('%-25s: %s (%s)', 'Source', $this->shortPath($this->source), $pattern));
		$this->out(sprintf('%-25s: %s', 'Destination', $this->shortPath($settings['filterDirectory'])));
		$this->out(sprintf('%-25s: %s', 'Image Version(s)', $version));
		$this->out(sprintf('%-25s: %s', 'Overwrite existing', $settings['overwrite'] ? 'yes' : 'no'));
		$this->out(sprintf('%-25s: %s', 'Create directories', $settings['createDirectory'] ? 'yes' : 'no'));

		if ($this->in('Looks OK?', 'y,n', 'y') == 'n') {
			return false;
		}
		$this->out();
		$this->out('Making');
		$this->hr();

		$Folder = new Folder($this->source);
		$files = $Folder->findRecursive($pattern);

		$this->progress(count($files));

		foreach ($files as $key => $file) {
			$this->progress($key, $this->shortPath($file));
			$this->_Model->make($file);
		}
		$this->out();
	}
}

?>
