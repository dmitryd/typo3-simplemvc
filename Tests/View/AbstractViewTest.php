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

// use DmitryDulepov\Simplemvc\Controller;

class AbstractViewTest extends \PHPUnit_Framework_TestCase {

		private $controller; 
		private $view; 

		/**
		* Setup before each test
		*/
		public function setUp() {
			require_once(__DIR__.'/../../Classes/View/AbstractView.php');
			require_once(__DIR__.'/../../Classes/Controller/AbstractController.php');

			$this->controller = $this->getMockBuilder('DmitryDulepov\Simplemvc\Controller\AbstractController')->disableOriginalConstructor()->getMock();

			$this->view = $this->getMockForAbstractClass('\DmitryDulepov\Simplemvc\View\AbstractView', array($this->controller));
		}

		/**
		* Cleanup after each test 
		*/
		public function tearDown() {
		}

		/**
		* @test
		*/
    public function the_test_environment_is_working() {
			$this->assertTrue(TRUE);
		}

		/**
		* @test
		*/
    public function loading_works() {
			$this->assertTrue(class_exists('DmitryDulepov\Simplemvc\View\AbstractView'));
			$this->assertTrue(class_exists('DmitryDulepov\Simplemvc\Controller\AbstractController'));
		}

		/**
		* @test
		*/
    public function the_constructor_works() {
			$this->assertInstanceOf('DmitryDulepov\Simplemvc\Controller\AbstractController', $this->controller);
			$this->assertInstanceOf('DmitryDulepov\Simplemvc\View\AbstractView', $this->view);
		}

		/**
		* @test
		*/
    public function getController_works() {
			$rm = new \ReflectionMethod('\DmitryDulepov\Simplemvc\View\AbstractView', 'getController');
			$rm->setAccessible(TRUE);
			$controller = $rm->invoke($this->view); 
			$this->assertSame($this->controller, $controller);
		}



	}
?>
