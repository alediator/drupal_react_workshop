<?php

namespace Drupal\drw\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class ReactController.
 */
class ReactController extends ControllerBase {

    public function content() {
        return [
            '#type' => 'markup',
            '#markup' => '<!--Container for React rendering-->
        <div class="container">
          <div class="col-md-4 col-md-offset-4">
            <div id="container"></div>
          </div>
        </div>',
            '#attached' => [
                'library' => [
                    'drw/drw',
                ],
            ],
        ];
    }

}
