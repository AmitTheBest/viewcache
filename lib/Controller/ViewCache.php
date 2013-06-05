<?php
/**
 * Enhances your view to automatically cache it's output. You must specify
 *
 * To use add this controller inside a view.
 */
namespace agile55/viewcache;
class Controller_ViewCache extends \AbstractController {

    /**
     * Set to custom key for cache. Will be used as "id" field of a model, so
     * in some cases your might make it numeric
     */
    public $key=null;

    /** 
     * 60 seconds is default timeout. After 60 seconds the cache will be nuked.
     */
    public $timeout=60;

    /** 
     * how many seconds to wait for lock. Set to max time of your rendering
     * phase. While one request tries to re-generate content, other requests
     * will wait
     */
    public $wait_seconds=5;

    /**
     * Will place hooks to intercept and cache standard view rendering. If you
     * intend to use cache() method you might set this to false. This is
     * especially important if you are using cache() inside render method,
     * as it will confuse the controller.
     */
    public $cache_render=true;

    public $model_name = 'agile55/viewcache/Model_ViewCache';

    function init(){
        parent::init();

        if(!$this->owner instanceof \View){
            throw $this->exception('This controller must be added inside a View');
        }

        $this->setModel($this->model_name);

        if(!$this->key)$this->key=$this->owner->name;

        $this->owner->addHook('pre-render',array($this,'preRender'));
    }

    function setKey($key){
        $this->key=$key;
        return $this;
    }

    /**
     * By default this method will cache "render" of your view. You can
     * however use this method to manually cache something:
     *
     * $output = $cache->cache(function($c){ sleep(5); return 'abc'; });
     *
     * In this example, the function with sleep will be executed once,
     * and cached, further calls to the method will return cached 'abc'
     * instead. Using this method will prevent standard behaviour of
     * capturing "render" method
     */
    function cache($callable){
        // Instead of default 
    }

    function preRender($v){


        $o=$this->getCachedOutput();
        if($o===null){
            $this->owner->addHook('output',array($this,'outputHook'));
            $this->api->addHook('post-render-output',array($this,'saveOutput'));
            $this->breakHook(false);   // regular render
        }
        $this->owner->output($o);
    }

    function saveOutput(){

        $expiration = time()+$this->timeout;
        $this->model->tryLoad($this->key?:$this->owner->name);
        $this->model['expiration']=time()+$this->timeout;
        $this->model['cache']=$this->output;
        $this->model->save($this->key);
    }


    /**
     * This hook will be used by view, to send output.
     */
    public $output='';
    function outputHook($v,$o){
        $this->output.=$o;
        $this->breakHook(null);
    }

    /**
     * Retrieve cache for the view. If cache is expired, get rid of it
     *
     * If setModel() wasn't used, then use memorize / recall instead
     */
    function getCachedOutput(){
        $this->model->tryLoad($this->key);
        if(!$this->model->loaded())return null;
        $o=$this->model;

        if($o['expiration']<time()){
            $this->model->delete();
            return null;
        }

        return $o['cache'];
    }
}
