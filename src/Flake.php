<?php

/**
 * Flake - a simple PHP templating engine
 *
 * @author      Deng Man <dengman2010@163.com>
 * @copyright   2018 icodechef
 * @link        http://icodechef.com/
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @version     1.0
 */

class Flake
{
    /**
     * 视图
     *
     * @var string
     */
    protected $view;

    /**
     * 布局视图实例
     *
     * @var \Flake
     */
    protected $layout;

    /**
     * 视图文件的路径
     *
     * @var string
     */
    protected $path;

    /**
     * 视图数据
     *
     * @var array
     */
    protected $data = [];

    /**
     * 已定位的视图数组
     *
     * @var array
     */
    protected $views = [];

    /**
     * 所有视图共享的数据
     *
     * @var array
     */
    protected static $shared = [];

    /**
     * 视图扩展名
     *
     * @var array
     */
    protected $extension = 'php';

    /**
     * 视图片段
     *
     * @var array
     */
    protected $sections = [];

    /**
     * 创建一个视图实例
     *
     * @param  string  $view
     * @param  string  $path
     * @param  array   $data
     * @return void
     */
    public function __construct($view, $path, array $data = [])
    {
        $this->view = $view;
        $this->setPath($path);
        $this->data = (array) $data;
    }

    /**
     * 创建并返回一个视图实例
     *
     * @param  string  $view
     * @param  array   $data
     * @return Flake
     */
    public function make($view, $mergeData = [])
    {
        $data = array_merge($this->data, $mergeData);
        return new self($view, $this->path, $data);
    }

    /**
     * 获取视图的内容
     *
     * @param  callable|null  $callback
     * @return string
     */
    public function render($callback = null)
    {
        try {
            $contents = $this->renderContents();
            $response = isset($callback) ? call_user_func($callback, $this, $contents) : null;
            return ! is_null($response) ? $response : $contents;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 获取视图实例的内容
     *
     * @return string
     */
    protected function renderContents()
    {
        $path = $this->find($this->view);
        $contents = $this->fetch($path, array_merge(static::$shared, $this->data));
        return $contents;
    }

    /**
     * 获取视图的内容
     *
     * @param  string  $path
     * @param  array   $data
     * @return string
     */
    public function fetch($path, array $data = [])
    {
        return $this->evaluatePath($path, $data);
    }

    /**
     * 获取给定路径中视图的内容
     *
     * @param  string  $__path
     * @param  array   $__data
     * @return string
     */
    protected function evaluatePath($__path, $__data)
    {
        ob_start();
        extract($__data, EXTR_SKIP);

        try {
            include $__path;
        } catch (\Exception $e) {
            throw $e;
        }

        $contents = trim(ob_get_clean());

        if ($this->layout) { // 视图布局
            $this->layout->sections = array_merge($this->sections, array('content' => $contents));
            $contents = $this->layout->render();
        }

        return $contents;
    }

    /**
     * 添加共享数据
     *
     * @param  array|string  $key
     * @param  mixed  $value
     * @return mixed
     */
    public static function share($key, $value = null)
    {
        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $key => $value) {
            static::$shared[$key] = $value;
        }

        return $value;
    }

    /**
     * 向视图添加数据
     *
     * @param  string|array  $key
     * @param  mixed   $value
     * @return $this
     */
    public function set($key, $value = null)
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    /**
     * 获取视图数据
     *
     * @param  string $key
     * @param  mixed $default
     * @param  mixed $filters
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (isset($this->data[$key])) {
            $value = $this->data[$key];
        } else if (isset(static::$shared[$key])) {
            $value = static::$shared[$key];
        } else {
            $value = $default;
        }

        return $value;
    }

    /**
     * 转义字符串
     *
     * @param  string $value
     * @param  boolean $doubleEncode
     * @return string
     */
    public function escape($value, $doubleEncode = true)
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', $doubleEncode);
    }

    /**
     * 过滤
     *
     * @param  mixed $string
     * @param  null|string|callback $rules
     * @return mixed
     */
    public function sanitize($value, $rules = null)
    {
        if (is_string($rules)) {
            foreach (explode('|', $rules) as $rule) {
                $parameters = [];
                if (strpos($rule, ':') !== false) {
                    // substr:0, 10
                    list($callback, $parameter) = explode(':', $rule, 2);
                    $parameters = str_getcsv($parameter);
                } else {
                    $callback = $rule;
                }
                array_unshift($parameters, $value);
                $value = call_user_func_array($callback, $parameters);
            }
        } else if (is_callable($rules)) {
            $value = call_user_func($rules, $value);
        }

        return $value;
    }

    /**
     * 将视图实例添加到视图数据
     *
     * @param  string  $key
     * @param  string  $view
     * @param  array   $data
     * @return $this
     */
    public function nest($key, $view, array $data = [])
    {
        return $this->set($key, $this->make($view, $data));
    }

    /**
     * 设置视图的路径
     *
     * @param  string  $path
     * @return void
     */
    public function setPath($path)
    {
        if (! is_dir($path)) {
            throw new \InvalidArgumentException(sprintf('The "%s" directory does not exist.', $path));
        }

        $this->path = rtrim($path, '/\\');
    }

    /**
     * 获取视图文件的路径
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * 注册扩展名
     *
     * @param  string  $extension
     * @return void
     */
    public function setExtension($extension)
    {
        $this->extension = (string) $extension;
    }

    /**
     * 获取扩展名
     *
     * @param  string  $extension
     * @return void
     */
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * 获取视图的位置
     *
     * @param  string  $name
     * @return string
     */
    public function find($name)
    {
        if (isset($this->views[$name])) {
            return $this->views[$name];
        }

        return $this->views[$name] = $this->findInPath($name);
    }

    /**
     * 查找视图的完整位置
     *
     * @param  string  $name
     * @return string
     */
    protected function findInPath($name)
    {
        $viewPath = $this->path.'/'.$name.'.'.$this->extension;

        if (file_exists($viewPath)) {
            return $viewPath;
        }

        throw new \InvalidArgumentException("View [{$name}] not found.");
    }

    /**
     * 确定给定视图是否存在
     *
     * @param  string  $view
     * @return bool
     */
    public function exists($view)
    {
        try {
            $this->find($view);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * 设置视图布局
     *
     * @param  string $layout
     * @param  array  $data
     * @return void
     */
    protected function extend($layout, array $data = [])
    {
        $this->layout = $this->make($layout, $data);
    }

    /**
     * 返回视图布局的内容片段
     *
     * @return string
     */
    public function content()
    {
        return isset($this->sections['content']) ? $this->sections['content'] : '';
    }

    /**
     * 开始定义一个视图片段
     *
     * * 一般 def() 与 end() 成对出现, 但传递第二个参数, 则不需要 end()
     *
     * @param  string $name
     * @param  string $content
     * @return null
     */
    protected function def($name, $content = '')
    {
        if ($name === 'content') {
            throw new \InvalidArgumentException('The section name "content" is reserved.');
        }

        if (! $content) {
            if (isset($this->sections[$name])) {
                $content = $this->sections[$name];
                unset($this->sections[$name]);
            }

            ob_start();
        }

        $this->sections[$name] = $content;
    }

    /**
     * 结束定义一个视图片段
     *
     * @param  string  $action
     * @return void
     */
    protected function end($action = 'append')
    {
        if ($action == 'append') {
            $this->append();
        } else {
            $this->override();
        }
    }

    /**
     * 附加视图片段
     *
     * @return void
     */
    protected function append()
    {
        if (empty($this->sections)) {
            throw new \LogicException('You must start a section before you can stop it.');
        }

        end($this->sections);

        $this->sections[key($this->sections)] .= ob_get_clean();
    }

    /**
     * 覆盖视图片段
     *
     * @return void
     */
    protected function override()
    {
        if (empty($this->sections)) {
            throw new \LogicException('You must start a section before you can stop it.');
        }

        end($this->sections);

        $this->sections[key($this->sections)] = ob_get_clean();
    }

    /**
     * 返回视图片断的内容
     *
     * @param  string      $name    视图片断名
     * @param  string      $default 默认的内容
     * @return string|null
     */
    protected function section($name, $default = null)
    {
        if (! isset($this->sections[$name])) {
            return $default;
        }

        return $this->sections[$name];
    }

    /**
     * 检查视图片段是否存在
     *
     * @param  string  $name
     * @return bool
     */
    public function hasSection($name)
    {
        return array_key_exists($name, $this->sections);
    }

    /**
     * 获取视图的字符串内容
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }
}
