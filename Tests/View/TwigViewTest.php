<?php
namespace DmitryDulepov\Simplemvc\Test;

use TYPO3\CMS\Core\Utility\GeneralUtility;

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
 * This class tests a Twig view.
 *
 * @author Dmitry Dulepov <dmitry.dulepov@gmail.com>
 */
class TwigViewTest extends \PHPUnit_Framework_TestCase {

	/** @var \DmitryDulepov\Simplemvc\Test\TwigTestController */
	protected $controller;

	/**
	 * Initializes the test.
	 *
	 * @return void
	 */
	public function setUp() {
		$this->controller = GeneralUtility::makeInstance('DmitryDulepov\\Simplemvc\\Test\\TwigTestController');
		$this->controller->cObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
	}

	/**
	 * Cleans up after the test.
	 *
	 * @return void
	 */
	public function tearDown() {
		unset($this->controller);
	}

	/**
	 * Tests that the default template is properly loaded from the Resources/
	 * directory when no template is supplied in the configuration.
	 *
	 * @return void
	 */
	public function testDefaultTwigTemplate() {
		$this->controller->setPostParameter('action', 'autoTemplate');
		$this->assertEquals('passed', $this->controller->main('', array()));
	}

	/**
	 * Tests that the template can be configured in the configuration.
	 *
	 * @return void
	 */
	public function testConfiguredTwigTemplate() {
		$this->controller->setPostParameter('action', 'configuredTemplate');
		$configuration = array(
			'simplemvc.' => array(
				'TwigTestController.' => array(
					'configuredTemplate.' => array(
						'twigTemplate' => 'EXT:simplemvc/Resources/Private/Templates/TwigTestController/autoTemplate.html.twig'
					)
				)
			)
		);
		$this->assertEquals('passed', $this->controller->main('', $configuration));
	}

}
