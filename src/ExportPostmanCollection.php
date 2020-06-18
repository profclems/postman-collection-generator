<?php


namespace Profclems\PostmanCollectionGenerator;


use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Routing\Router;

class ExportPostmanCollection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'postman:collection:export {name?}
                            {--api} {--web} {--url={{base_url}}} {--port=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate postman collection from api/web routes';

    /**
     * @var Filesystem
     */
    private $_files;

    /**
     * @var Router
     */
    private $_router;

    /**
     * Create a new command instance.
     *
     * @param Router     $_router
     * @param Filesystem $_files
     */
    public function __construct(Router $_router, Filesystem $_files)
    {
        $this->_files = $_files;
        $this->_router = $_router;
        parent::__construct();
    }

    /**
     * @param null $data
     *
     * @return string
     * @throws Exception
     */
    public function uuidv4($data = null) : string
    {
        $data = $data ?? random_bytes(16);
        assert(strlen($data) == 16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() : void
    {
        $filename = $this->argument('name');

        if ($this->option('api') || $this->option('api')) {

            $routeType = $this->option('api')? 'api':'web';
            $filename = $filename??config('app.name') . '_postman';
            $url = $this->option('url');
            $url = $this->option('port') ? $url . ':' . $this->option('port') : $url;

            $routes = [
                'variables' => [],
                'info'      => [
                    'name'        => $filename . '_' . $routeType,
                    '_postman_id' => $this->uuidv4(),
                    'description' => '',
                    'schema'      => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
                ],
            ];

            foreach ($this->_router->getRoutes() as $route) {
                foreach ($route->methods as $method) {
                    if ('HEAD' == $method) {
                        continue;
                    }

                    //GETTING @PARAMs @VARs @DESCRIPTIONs from PhpDoc comments
                    $p = $this->getParams($route);

                    //API ROUTES
                    if ($this->option('api') && "api" == $route->middleware()[0]) {
                        $routes['item'][] = [
                            'name'     => $method . ' | ' . $route->uri(),
                            'request'  => [
                                'auth'        => '',
                                'method'      => strtoupper($method),
                                'header'      => [
                                    [
                                        'key'         => 'Content-Type',
                                        'value'       => 'application/json',
                                        'description' => $p['description'],
                                    ],
                                ],
                                'body'        => [
                                    'mode' => 'raw',
                                    'raw'  => '{\n    \n}',
                                ],
                                'url'         => [
                                    'raw'   => $url . '/' . $route->uri(),
                                    'host'  => $url . '/' . $route->uri(),
                                    'query' => $p['paramsArray'],
                                ],
                                'description' => $p['description'],
                            ],
                            'response' => [],
                        ];
                    } else if ($this->option('web') && "web" == $route->middleware()[0]) {
                        //WEB ROUTES
                        $routes['item'][] = [
                            'name'     => $method . ' | ' . $route->uri(),
                            'request'  => [
                                'url'         => $url . '/' . $route->uri(),
                                'params'      => [
                                    'key'         => '',
                                    'value'       => '',
                                    'description' => '',
                                ],
                                'method'      => strtoupper($method),
                                'header'      => [
                                    [
                                        'key'         => 'Content-Type',
                                        'value'       => 'text/html',
                                        'description' => '',
                                    ],
                                ],
                                'body'        => [
                                    'mode' => 'raw',
                                    'raw'  => '{\n    \n}',
                                ],
                                'description' => $p['description'],
                            ],
                            'response' => [],
                        ];
                    }
                }
            }

            $exportFile = $filename . '_' . $routeType . '.json';

            if (!$this->_files->put($exportFile, json_encode($routes))) {
                $this->error('Export failed');
            } else {
                $this->info('Routes exported! Filename: ' . $exportFile);
            }

        } else {
            $this->error('Please use --api or --web to specify the type of route file to export');
        }
    }

    public function getParams($route) {
        if (empty($route->action['controller'])) {
            return false;
        }

        $controller = $route->action['controller'];

        $file = str_replace('\\', '/', $controller);
        $file = explode('/', $file);
        if ('App' !== $file[0]) {
            array_unshift($file, 'vendor');
        } else {
            $file[0] = 'app';
        }
        $file = base_path() . '/' . implode('/', $file);
        $file = strstr($file, '@', true) . '.php';

        try {
            @$file_open = fopen($file, "r");
        } catch (Exception $e) {

        }

        /**
         * @dev Reading file and search comments
         */
        if ($file_open) {
            $file_string = fread($file_open, filesize($file));

            // getting function name
            $function_name = explode('@', $controller);
            $function_name = $function_name[1];
            $route_part    = substr($file_string, 0, mb_strpos($file_string, $function_name));

            if (null != $route_part) {
                // getting commented strokes for function
                preg_match_all("~//?\s*\*[\s\S]*?\*\s*//?~m", $route_part, $comments, PREG_OFFSET_CAPTURE);
                $comment = end($comments[0])[0]??null;

                //@description
                preg_match_all("~@description([\s\S]*? )([\s\S]*?)\\@~", $comment, $descriptions, PREG_PATTERN_ORDER);
                if (!empty(end($descriptions)[0])) {
                    $description      = $this->cleanString(end($descriptions)[0]);
                    $p['description'] = trim($description);
                } else {
                    $p['description'] = '';
                }

                //@param
                preg_match_all("~@param(.*)~", $comment, $params, PREG_PATTERN_ORDER);
                if (!empty(end($params)[0])) {
                    foreach ($params[1] as $key => $param) {
                        $param = explode(' ', $this->cleanString($param));
                        //type
                        $p['paramsArray'][$key]['type'] = array_shift($param);
                        //name
                        $p['paramsArray'][$key]['key'] = array_shift($param);
                        //description
                        $p['paramsArray'][$key]['description'] = implode(' ', $param);
                    }
                } else {
                    $p['paramsArray'] = '';
                }

                //@var
                preg_match_all("~@var(.*)~", $comment, $vars, PREG_PATTERN_ORDER);
                if (!empty(end($vars)[0])) {
                    foreach ($vars[1] as $key => $var) {
                        $var = explode(' ', $this->cleanString($var));
                        //type
                        $p['varsArray'][$key]['type'] = array_shift($var);
                        //name
                        $p['varsArray'][$key]['key'] = array_shift($var);
                        //description
                        $p['varsArray'][$key]['description'] = implode(' ', $var);
                    }
                } else {
                    $p['varsArray'] = '';
                }

                //@return
                preg_match_all("~@return(.*)~", $comment, $returns, PREG_PATTERN_ORDER);
                if (!empty(end($returns)[0])) {
                    $p['return'] = $this->cleanString($returns[1][0]);
                } else {
                    $p['return'] = '';
                }
            }

            unset($param, $value, $description, $c);
        }

        if (!isset($p) || empty($p)) {
            $p['return']      = '';
            $p['response']    = '';
            $p['param']       = '';
            $p['description'] = '';
            $p['paramsArray'] = '';
            $p['varsArray']   = '';
        }
        return $p;
    }

    public function cleanString($string) {
        $string = str_replace('*', '', $string); // Replaces
        $string = str_replace('#', '', $string); // Replaces
        $string = str_replace('  ', '', $string); // Replaces
        if (substr($string, -1) == '@') {
            $string = substr_replace($string, "", -1);
        } // Removes last @
        $string = preg_replace('/[\n\t*]/', '', $string); // Removes special chars.
        return trim($string);
    }

}
