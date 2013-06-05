<?php
namespace agile55/viewcache;
class Model_ViewCache extends \Model {
    public $table='viewcache';
    function init(){
        parent::init();

        $this->addField('expiration');
        $this->addField('cache')->type('text');
        $this->addField('lock')->type('boolean');

        $this->setSource('Memcached');
    }

}
