# apiDocs
laravel(>=8.12)

## 配置
> app_path()/ApiDocs/Kernel.php 中追加
```
 \AndPHP\Postman\PostmanToMarkdownCommand::class,
```
## 创建 postman导出的josn文件
> 路径: app_path()/ApiDocs/PostmanJson (没有文件夹，可自行创建)

> 事例

## 执行命令

```
php artisan docs:postmanMd
```