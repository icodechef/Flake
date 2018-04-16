# Flake
Flake 一个简单而又强大的模板引擎。支持 `视图继承` 和 `视图片段` 功能，使用原生的PHP代码，帮助你灵活地控制页面布局。

例如视图被保存在 `resources/views` 文件夹内。

```html
<!-- 视图被保存在 resources/views/index.php -->
<html>
    <body>
        <h1>Hello, <?php echo $name; ?></h1>
    </body>
</html>
```

这个视图可以使用以下的代码传递到用户的浏览器：

```php
$flake = new Flake('index', 'resources/views', [
    'name' => 'World!',
]);

$flake->render();
```

如你所见，`Flake` 构造函数的第一个参数为视图文件；第二个参数为对应到视图文件的位置; 第三个参数是一个能够在视图内取用的数据数组。

### 视图数据

向视图传递数据后，可以在视图文件中获取以 `键名` 作为变量名的值。
在下面的例子代码中，视图将可以使用 `$total`，`$fruits` 来取得数据，其值分别为 `100`，`array('apple', 'banana')`。

```php
$flake = new Flake('index.php', 'resources/views', [
    'total' => 100,
    'fruits' => ['apple', 'banana'],
]);

$flake->render();
```

在视图文件中：

```html
<p><?php echo $total; ?></p>
<ul>
<?php foreach ($fruits as $fruit) { ?>
    <li><?php echo $fruit; ?></li>
<?php } ?>
</ul>
```

> 在视图中，可访问 $this 指向 Flake 对象来管理和渲染这个视图文件。

所以我们可以使用用视图对象的 `get()` 方法来获取视图数据，使用 `get()` 方法的好处是可以设置数据不存在时的默认值：

```html
<p><?php echo $this->get('total'); ?></p>
<ul>
<?php foreach ($this->get('fruits', []) as $fruit) { ?>
    <li><?php echo $fruit; ?></li>
<?php } ?>
</ul>
```

### 视图继承和视图片段

大多数页面应用都有相同的页头和页尾，传统的方法是把相同的页头和页尾存在不同的文件或者函数，然后按顺序加载。

```html
<!-- 页头 header.php -->
<html>
<head>
    <title>title</title>
</head>
<body>
<header>Header</header>
```

```html
<!-- 页尾 footer.php -->
<footer>footer</footer>
</body>
</html>
```

```html
<!-- 视图 -->
<?php require('header.php') ?>
<article>
    ......
</article>
<?php require('footer.php') ?>
```

我们可以看到这种方法将一个 HTML 整体分割为三部分，但这样做，当每一个页面的页头和页尾不同时，我们就需要添加判断或者新建不同的页头和页尾文件。

视图继承和视图片段将这些公共的部分(如页头和页尾)放到一个布局中，渲染内容视图后在合适的地方嵌入到布局中。

定义一个页面布局

```html
<!-- 视图被保存在 resources/views/layout/main.php -->
<html>
<head>
    <meta charset="utf-8">
    <title>Name: <?php $this->section('title', '视图标题'); ?></title>
</head>
<body>
    <?php $this->section('header') ?>
    <?php $this->content(); ?>
    <footer>我是页脚</footer>
</body>
</html>
```

在视图模板中使用页面布局

```html
<!-- 视图被保存在 resources/views/index.php -->
<?php $this->extend('main.php'); // 视图继承 ?>

<!-- 这区域是视图的内容 -->
Hello, <?php echo $name; ?>
<!-- 这区域是视图的内容 -->

<?php $this->def('title', '我是首页'); // 定义一个视图片段 ?>

<?php $this->def('header'); // 开始定义一个视图片段 ?>
<header>我是头部</header>
<?php $this->end(); // 结束定义一个视图片段 ?>
```

如你所见，在视图模板中，使用 `extend()` 方法继承一个页面布局，方法的参数为页面布局所在的文件。

使用 `section()` 方法在视图中定义一个视图片段，参数为 `片段的名称` 和 `片段的默认内容`，如 `$this->section('title', '视图标题')`、 `$this->section('header')`。

视图片段的内容定义在 `def()` 和 `end()` 之间。如：

```php
<?php $this->def('header'); // 开始定义一个视图片段, 片段的名称为 header ?>
<header>我是头部</header>
<?php $this->end(); // 结束定义一个视图片段 ?>
```

注意，一般 `def()` 和 `end()` 方法应该成对出现, 但传递第二个参数，则不需要 `end()` 了，否则输出并不是你想要的。

```php
$this->def('title', '这里是一个标题');

// 或者
$this->def('title');
这里是一个标题。
$this->end();
```

在页面布局(也就是父模板)中使用 `content()` 方法返回继承自其的视图内容。

```php
<div><?php $this->content(); ?></div>
```

### 嵌套视图

```html
<html>
...
    <?php $this->nest('header.php'); ?>
    ...
    <?php $this->nest('footer.php'); ?>
...
</html>
```
