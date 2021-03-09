<?php
class Router
{

    protected $routes = []; // routes
    protected $middlewares = []; // middlewares
    protected $basePath = ''; // prefixe sous dossier si index.php n'est pas à la racine
    protected $sensitive = false; // si false, alors /test & /test/ matchent la meme route, peut importe le slash final
	// types pour regex perso :
    protected $matchTypes = [
        'i'  => '[0-9]++',
        'a'  => '[0-9A-Za-z]++',
        'h'  => '[0-9A-Fa-f]++',
        '*'  => '.*?',
        ''   => '[^/\.]++'
    ];

    public function __construct($basePath = '') { $this->setBasePath($basePath); }
	
    public function getRoutes() { return $this->routes; }
    public function getMiddlewares() { return $this->middlewares; }
	public static function redirect($url) { header('Location: '.'/'.ltrim($url, '/'));die(); }
	public static function throw404() { header('HTTP/1.0 404 Not Found', true, 404);die(); }
	public static function crud($a) { $a = str_replace('_', '\_', implode('|',$a)); return '{('.$a.'):table}{((\/[1-9]*[0-9]+)|(\/create))?:action}'; }
	
    public function setBasePath($basePath) { $this->basePath = $basePath; }

	// ajoute une route à la liste des routes possibles
    public function map($method, $route, $target)
    {
        $this->routes[] = [$method, $route, $target];
        return;
    }
	
	// ajoute un middleware a la route
    public function mapMiddleware($route, $target)
    {
		if(!isset($this->middlewares[$route]))
			$this->middlewares[$route] = array();
        $this->middlewares[$route][] = $target;
        return;
    }
	
	public function getRequestUrl() {
        $requestUrl = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';

        $requestUrl = substr($requestUrl, strlen($this->basePath));
		
		if(!$this->sensitive)
			$requestUrl = rtrim($requestUrl, '/').'/';

        if (($strpos = strpos($requestUrl, '?')) !== false) {
            $requestUrl = substr($requestUrl, 0, $strpos);
        }
		return $requestUrl;
	}

	// trouve la route correspondant a la requete
    public function match()
    {

        $params = [];

		$requestUrl = $this->getRequestUrl();

        $lastUrlChar = $requestUrl[strlen($requestUrl)-1];

        $requestMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        foreach ($this->routes as $handler) {
            list($methods, $route, $target) = $handler;

            $method_match = (stripos($methods, $requestMethod) !== false);

            if (!$method_match) {
                continue;
            }
			
			if(!$this->sensitive)
				$route = rtrim($route, '/').'/';

            if ($route === '*') {
                $match = true;
            } elseif (isset($route[0]) && $route[0] === '@') {
                $pattern = '`' . substr($route, 1) . '`u';
                $match = preg_match($pattern, $requestUrl, $params) === 1;
            } elseif (($position = strpos($route, '{')) === false) {
                $match = strcmp($requestUrl, $route) === 0;
            } else {
                if (strncmp($requestUrl, $route, $position) !== 0 && ($lastUrlChar === '/' || $route[$position-1] !== '/')) {
                    continue;
                }

                $regex = $this->compileRoute($route);
				//var_dump($regex);
                $match = preg_match($regex, $requestUrl, $params) === 1;
            }

            if ($match) {
                if ($params) {
                    foreach ($params as $key => $value) {
                        if (is_numeric($key)) {
                            unset($params[$key]);
                        }
                    }
                }

                return [
					'route' => $route,
                    'target' => $target,
                    'params' => $params
                ];
            }
        }

        return false;
    }
	
	// execute le callback de la route trouvee
	public function dispatch() {
		$match = $this->match();
		if($match) { 
			$continue = true;
			foreach($this->middlewares as $km=>$middleware) {
				if((substr($km,-1)=='*' && substr($match['route'],0,strlen($km)-1)==substr($km,0,strlen($km)-1)) || $match['route']==$km) {
					foreach($this->middlewares[$km] as $mw) {
						$continue = call_user_func($mw, $match['params']);
						if($continue!==true)
							break;
					}
				}
			}
			if(!$continue) {
				Router::throw404();
			}
			return call_user_func_array($match['target'], $match['params']);
		}
		else { 
			return Router::throw404();
		}		
	}


	// compile la regex pour this->match
    protected function compileRoute($route)
    {
        if (preg_match_all('`(/|\.|)\{([^:\}]*+)(?::([^:\}]*+))?\}(\?|)`', $route, $matches, PREG_SET_ORDER)) {
            $matchTypes = $this->matchTypes;
            foreach ($matches as $match) {
                list($block, $pre, $type, $param, $optional) = $match;

                if (isset($matchTypes[$type])) {
                    $type = $matchTypes[$type];
                }
                if ($pre === '.') {
                    $pre = '\.';
                }

                $optional = $optional !== '' ? '?' : null;

                $pattern = '(?:'
                        . ($pre !== '' ? $pre : null)
                        . '('
                        . ($param !== '' ? "?P<$param>" : null)
                        . $type
                        . ')'
                        . $optional
                        . ')'
                        . $optional;

                $route = str_replace($block, $pattern, $route);
            }
        }
        return "`^$route$`u";
    }
}