<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 27.08.2018
 * Time: 15:15
 */

namespace EApp\View;

/**
 * Trait ViewDevelopTrait
 *
 * This is empty trait for developers
 * You may decelerated this trait for your modules and defined custom method for proxy \EApp\View\View instance
 *
 * @package EApp\View
 */
trait ViewDevelopTrait {}

/* example

// FOR FILE view_develop_trait.noinclude.php

<?php exit;

namespace EApp\View;

/**
 * @method string getUrl(string $controller, int $id, \EApp\Component\Context $context = null)
 * /
trait ViewDevelopTrait {}

// FOR TEMPLATE USE

<?= $view->getUrl("my::controller", 1) ?>

*/