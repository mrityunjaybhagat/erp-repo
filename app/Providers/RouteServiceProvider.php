// Source - https://stackoverflow.com/a/39462686
// Posted by haris, modified by community. See post 'Timeline' for change history
// Retrieved 2026-09-19, License - CC BY-SA 4.0

    Route::group([
        'middleware' => ['api', 'cors'],
        'namespace' => $this->namespace,
        'prefix' => 'api',
    ], function ($router) {
         //Add you routes here, for example:
         Route::apiResource('/posts','PostController');
    });
