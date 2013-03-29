<?php
namespace DmitryDulepov\Simplemvc\View;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Dmitry Dulepov <dmitry.dulepov@gmail.com>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * This is an abstract view for the SimpleMVC framework. It provides interaction
 * with other classes.
 *
 * @author Dmitry Dulepov <dmitry.dulepov@gmail.com>
 */
abstract class AbstractView {

	/** @var \DmitryDulepov\Simplemvc\Controller\AbstractController */
	private $controller;

	/**
	 * Data to render with the view. Not all views use this. This is set to the
	 * result of the action in the controller if the action returns an object
	 * or an array. Extensions can use this to render such results automatically
	 * with their view classes.
	 *
	 * @var mixed
	 */
	protected $data = null;

	/**
	 * Creates the instance of this class.
	 *
	 * @param \DmitryDulepov\Simplemvc\Controller\AbstractController $controller
	 */
	public function __construct(\DmitryDulepov\Simplemvc\Controller\AbstractController $controller) {
		$this->controller = $controller;
	}

	/**
	 * Renders the content of the view.
	 *
	 * @return string
	 */
	abstract public function render();

	/**
	 * Sets the data to render with the view. Optional.
	 *
	 * @param mixed $data
	 * @return void
	 */
	public function setData($data) {
		$this->data = $data;
	}

	/**
	 * Obtains the current controller.
	 *
	 * @return \DmitryDulepov\Simplemvc\Controller\AbstractController
	 */
	protected function getController() {
		return $this->controller;
	}
}
