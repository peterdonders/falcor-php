<?php

class TestRoute extends Route {

    public function get() {
        $return = new stdClass;

        $return->{$this->route} = "Test me";
        return $return;
    }

}