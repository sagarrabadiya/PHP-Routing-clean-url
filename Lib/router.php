<?php
/**
 *
 * @author: Sagar
 * Date: 3/26/14
 * Time: 11:26 AM
 */

if( !defined('APP') ) echo "Please use the script with entry point, you can't access script directly!";

/**
 * Class command
 */
class command
{
    /**
     * @var
     */
    private $_controller;
    /**
     * @var
     */
    private $_parameters;

    /**
     * @param $controller
     * @param $parameters
     */
    public function __construct($controller,$parameters = array())
    {
        $this->_controller = $controller;
        $this->_parameters = $parameters;
    }

    /**
     * @return mixed
     */
    public function getController()
    {
        return $this->_controller;
    }

    /**
     * @return mixed
     */
    public function getParameters()
    {
        return $this->_parameters;
    }

}


/**
 * Class UrlInterpreter
 */
class UrlInterpreter extends command
{
    /**
     *
     */
    function __construct()
    {

    }

    /**
     * @param string $defaultController
     * @return command
     */
    public function getCommand($defaultController = '')
    {
        $aFullUrl = explode('/',$_SERVER['REQUEST_URI']);
        $aScript = explode('/',$_SERVER['SCRIPT_NAME']);

        $aCommand = array_diff($aFullUrl,$aScript);
        $aCommand = array_values($aCommand);

        $sController = $defaultController;
        $aParameters = array();
        if( !empty( $aCommand ) )
        {
            $sController = $aCommand[0];

            unset($aCommand[0]);
            $aCommand = array_values($aCommand);
            $aParameters = $aCommand;

        }
        $oCommand = new command($sController,$aParameters);
        return $oCommand ;
    }
}

/**
 * Class dispatcher
 */
class dispatcher
{
    /**
     * @var command
     */
    private $_command;
    /**
     * @var string
     */
    private $_scriptLocation = 'controllers/';

    /**
     * @var string
     */
    private $_defaultController = 'welcome';

    /**
     * @var UrlInterpreter
     */
    private $_UrlInterpreter;

    /**
     * @param command $command
     */
    public function __construct()
    {
        $this->_UrlInterpreter = new UrlInterpreter();
        $this->_command = $this->_UrlInterpreter->getCommand();
    }

    /**
     * @param $sControllerName
     * @return bool
     */
    private function isController($sControllerName)
    {
        if( file_exists(APP."/".$this->_scriptLocation.$sControllerName.".php"))
            return true;

        return false;
    }

    /**
     * @param $sScriptPath
     */
    public function setScriptPath($sScriptPath)
    {
        $this->_scriptLocation = $sScriptPath;
    }

    /**
     * @param $sController
     */
    public function setDefaultController( $sController )
    {
        $this->_defaultController = $sController;

        $this->_command = $this->_UrlInterpreter->getCommand($sController);
    }

    /**
     * to dispatch request to apropriate controller
     */
    public function dispatch()
    {
        if( $this->isController( $this->_command->getController() ) )
        {
            require_once APP."/".$this->_scriptLocation.$this->_command->getController().".php";
            $sControllerName = $this->_command->getController();
            if(class_exists($sControllerName))
            {
                $oController = new $sControllerName();

                $aParameter = $this->_command->getParameters();

                if( empty( $aParameter ) )
                    call_user_func(array($oController,'index'));
                else
                {
                    $sFunctionName = $aParameter[0];
                    unset($aParameter[0]);
                    $aParameter = array_values($aParameter);
                    if( method_exists($oController,$sFunctionName) )
                    {
                        call_user_func_array(array($oController,$sFunctionName),$aParameter);
                    }
                    else
                    {
                        $this->notFound();
                    }
                }
            }
            else
            {
                $this->notFound();
            }
        }
        else
        {
            $this->notFound();
        }
    }

    /**
     *
     */
    public function notFound()
    {
        header('HTTP/1.0 404 Not Found');
        echo "<h1>404 Not Found</h1>";
        echo "The page that you have requested could not be found.";
        exit();
    }
}