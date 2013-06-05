View Cache for Agile Toolkit (for Memcached)
====

If you run a busy site with complex view objects, it's likely you would want to render them and keep them in cache instead. Agile Toolkit comes with no built-in caching mechanisms for view, however this simple add-on will add you exactly this functionality.

    $view->add('agile55/viewcache/Controller_ViewCache');
    
This is all you need to add inside your view to make cache work. By default object is cached for 1 minute. 

There are number of properties you can specify to the controller:

`key` - Set name of the cache entity manually
----

By default cache will use name of your view as a key. This way it can make sure cache of first view is not overlapping with second view. You can specify key yourself which makes it easier to invalidate cache. 

You can also use `setKey()` method on existing cache.

    $cache->setKey('user_'.$user_id);
    
Here is example of invalidation. Put that inside user model:

    $this->addHook('beforeSave',function($m){
        $m->add('agile55/viewcache/Model_ViewCache');
        $m->tryLoad('user_'.$m->id);
        if($m->loaded())$m->delete();
    });

`timeout` - Setting invalidation time for cache
----
When cache is loaded, the model contains field with expiration date. This date is set when cache is created. I recommend you to add a little randomness for this timeout, to make sure your caches do not expire simultaneously.

    $view->add('agile55/viewcache/Controller_ViewCache', array('timeout'=>rand(50,70)));
    
This code will give you 60 seconds average expiration.


`model_name` - Use custom model
----
By default ViewCache comes with controller and model. Model is defined out of 3 fields and is using 'Memcached' data controller:

        $this->addField('expiration');
        $this->addField('cache')->type('text');
        $this->addField('lock')->type('boolean');
        $this->setSource('Memcached');

If you are willing to use a different model or store cache elsewhere, you can supply a different model. Just make sure that the same set of fields exists. It's probably a good idea to extend your cache model from agile55\viewcache\Model_ViewCache


Locking (TODO feature - Not Implemented)
----
Locking is a very vital part of Caching mechanism to avoid race conditions. Suppose you handle a busy site and cache expired for one of your major view objects. Within seconds you'll have number of requests lined up and all of them would be rendering your view. This may make your site temporarily unavailable, until cache is generated and queued requests are cleaned up from your web server.


When ViewCache attempts to load HTML data from cache, it will attempt to create a new locked record in memcached. If it was successful, that means this particular process is responsible for creating new cache. 

If adding new record was unsuccessful, then entry will be loaded, will check to make sure lock is no more than `$wait_seconds` seconds old (to avoid stale locks), then will sleep for 1 second and re-check the lock again.


Custom Caching (TODO feature - Not Implemented)
----
The method `cache()` allows you to cache arbitrary data of an object. Here is usage:

    $cache = $this->add('agile55/viewcache/Controller_ViewCache');
    
    $output = $cache->cache(function($cache){
    
        return execute_complex_operation_here();
    
    });

This way the callback will be called only if cache is expired.

 * TODO: allow adding controller into non-views
 * TODO Stop default behavior if cache() is used.