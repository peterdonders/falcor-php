<?php
#[\AllowDynamicProperties]
class Route {

    public $route;


    public function get() {

        $return = new stdClass;

        $return->{$this->route} = "Hello World";
        return $return;
    }

}