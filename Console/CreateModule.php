<?php

namespace Vheins\LaravelModuleGenerator\Console;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class CreateModule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:module {module} {--fillable=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Module Scaffold';

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['module', InputArgument::REQUIRED, 'The name of module will be created.'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['fillable', null, InputOption::VALUE_REQUIRED, 'The specified fields table.'],
        ];
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->module = Str::studly($this->argument('module'));
        $this->fields = $this->option('fillable');
        //dd($this->module, $this->fields);

        //Generate Module
        $this->call('module:make', [
            'name' => [$this->module],
            '--api' => true
        ]);

        //Generate Module
        $this->call('create:module:controller', [
            'controller' => $this->module,
            'module' => $this->module,
            '--api' => true
        ]);

        //Generate Model
        $this->call('create:module:model', [
            'model' => $this->module,
            'module' => $this->module,
            '--fillable' => $this->fields,
            '--migration' => true
        ]);

        //Generate Create Request
        $this->call('create:module:request', [
            'name' => $this->module . 'StoreRequest',
            'module' => $this->module,
            '--fillable' => $this->fields,
        ]);
        //Generate Update Request
        $this->call('create:module:request', [
            'name' => $this->module . 'UpdateRequest',
            'module' => $this->module,
            '--fillable' => $this->fields,
        ]);

        //Generate Create Action
        $this->call('create:module:action', [
            'name' => $this->module . 'Store',
            'module' => $this->module,
        ]);
        //Create Store Action
        $storeActionFile = base_path() . "/modules/" . $this->module . "/Actions/" . $this->module . "Store.php";
        $storeAction = file_get_contents($storeActionFile);
        $storeAction = str_replace('//use .. ;', "use Vheins\\$this->module\\Models\\" . $this->module . ";\nuse Vheins\\$this->module\\Requests\\" . $this->module . "StoreRequest;", $storeAction);
        $storeAction = str_replace('public function handle($handle)', 'public function handle(' . $this->module . 'StoreRequest $request)', $storeAction);
        $storeAction = str_replace('// ..', '$handle = ' . $this->module . '::create($request->validated());', $storeAction);
        file_put_contents($storeActionFile, $storeAction);

        //Generate Update Action
        $this->call('create:module:action', [
            'name' => $this->module . 'Update',
            'module' => $this->module,
        ]);
        //Create Update Action
        $updateActionFile = base_path() . "/modules/" . $this->module . "/Actions/" . $this->module . "Update.php";
        $updateAction = file_get_contents($updateActionFile);
        $updateAction = str_replace('//use .. ;', "use Vheins\\$this->module\\Models\\" . $this->module . ";\nuse Vheins\\$this->module\\Requests\\" . $this->module . "UpdateRequest;",  $updateAction);
        $updateAction = str_replace('public function handle($handle)', 'public function handle(' . $this->module . 'UpdateRequest $request, ' . $this->module . ' $' . Str::camel($this->module) . ')', $updateAction);
        $updateAction = str_replace('// ..', '$' . Str::camel($this->module) . '->update($request->validated());', $updateAction);
        $updateAction = str_replace('return $handle;', 'return $' . Str::camel($this->module) . ';', $updateAction);
        file_put_contents($updateActionFile, $updateAction);

        //Generate Delete Action
        $this->call('create:module:action', [
            'name' => $this->module . 'Delete',
            'module' => $this->module,
        ]);
        $deleteActionFile = base_path() . "/modules/" . $this->module . "/Actions/" . $this->module . "Delete.php";
        $deleteAction = file_get_contents($deleteActionFile);
        $deleteAction = str_replace('//use .. ;', "use Vheins\\$this->module\\Models\\" . $this->module . ";", $deleteAction);
        $deleteAction = str_replace('public function handle($handle)', 'public function handle(' . $this->module . ' $' . Str::camel($this->module) . ')', $deleteAction);
        $deleteAction = str_replace('// ..', '$handle = collect($' . Str::camel($this->module) . '->delete());', $deleteAction);
        file_put_contents($deleteActionFile, $deleteAction);


        //Fix Route File
        $routeApiFile = base_path() . "/modules/" . $this->module . "/api.php";
        $routeApi = file_get_contents($routeApiFile);
        $routeApi = str_replace('$API_ROUTE$', $this->pageUrl($this->module), $routeApi);
        file_put_contents($routeApiFile, $routeApi);

        // //Fix Controller File
        // $controllerFile = base_path() . "/modules/" . $this->module . "/Controllers/" . Str::studly($this->module) . "Controller.php";
        // $controller = file_get_contents($controllerFile);
        // $controller = str_replace('$modelVar$', Str::camel($this->module), $controller);
        // file_put_contents($controllerFile, $controller);


        //Generate Vue
        $commands = [
            'create:module:vue:store',
            'create:module:vue:page:index',
            'create:module:vue:page:new',
            'create:module:vue:page:view',
            'create:module:vue:component:link',
            'create:module:vue:component:tab',
            'create:module:vue:component:form',
        ];
        foreach ($commands as $command) {
            $this->call($command, [
                'name' => $this->module,
                'module' => $this->module,
                '--fillable' => $this->fields,
            ]);
        }
    }

    private function pageUrl($text)
    {
        return Str::of($text)->headline()->plural()->slug();
    }
}
