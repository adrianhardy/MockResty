# MockResty

I've extended [Resty](https://github.com/fictivekin/resty.php) so that it's 
easy to mock responses. At the moment, it's very simple - given an HTTP method
and a URL, we "send back" a static string response. That's it.

```php
$resty = new \MockResty\MockResty();

// send back a json response
$resty->on('get','/version', json_encode(array('version'=>'0.99b')));

// change the response header to 403, but send back an empty body
$resty->on('get','/auth/forbidden', 'HTTP/1.1 403 Forbidden');
```

At present, MockResty is very dumb - it won't auto-json the responses, for 
example. It will simply return what has been set. 
